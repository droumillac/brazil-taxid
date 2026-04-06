<?php

namespace BrazilTaxId\Providers;

use BrazilTaxId\Contracts\TaxIdProviderInterface;
use BrazilTaxId\Contracts\CpfProviderInterface;
use BrazilTaxId\Models\CompanyData;
use BrazilTaxId\Models\PersonData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class SerproProvider implements TaxIdProviderInterface, CpfProviderInterface
{
    private Client $client;
    private ?string $consumerKey;
    private ?string $consumerSecret;
    private ?string $bearerToken;
    private string $cnpjBaseUrl;
    private string $cpfBaseUrl;
    private string $authUrl;

    public function __construct(
        string $consumerKeyOrToken,
        ?string $consumerSecret = null,
        Client $client = null,
        string $cnpjBaseUrl = 'https://gateway.apiserpro.serpro.gov.br/consulta-cnpj-df/v2/empresa/',
        string $cpfBaseUrl = 'https://gateway.apiserpro.serpro.gov.br/consulta-cpf-df/v2/cpf/',
        string $authUrl = 'https://gateway.apiserpro.serpro.gov.br/token'
    ) {
        $this->client = $client ?? new Client(['timeout' => 5.0]);

        // Se omitirmos a secret, a biblioteca assume que o usuário
        // está colando diretamente o Bearer Token fixo
        if (is_null($consumerSecret)) {
            $this->bearerToken = $consumerKeyOrToken;
            $this->consumerKey = null;
            $this->consumerSecret = null;
        } else {
            $this->consumerKey = $consumerKeyOrToken;
            $this->consumerSecret = $consumerSecret;
            $this->bearerToken = null;
        }

        $this->cnpjBaseUrl = rtrim($cnpjBaseUrl, '/') . '/';
        $this->cpfBaseUrl = rtrim($cpfBaseUrl, '/') . '/';
        $this->authUrl = $authUrl;
    }

    public function getName(): string
    {
        return 'Serpro';
    }

    private function getCacheFilePath(): string
    {
        // Usamos uma base md5 na chave para permitir múltiplas contas/chaves 
        // hospedadas na mesma VPS operarem simulaneamente sem subscreverem seus arquivos
        $keyPrefix = $this->consumerKey ? md5($this->consumerKey) : 'static';
        // A segurança extrema se dá por esconder isso na temp dir, E com a extensão .php munida de die()
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'braziltax_serpro_' . $keyPrefix . '.php';
    }

    private function getAccessToken(bool $forceRefresh = false): ?string
    {
        if (!$this->consumerKey) {
            // Não há consumer_key, apenas token estático passado pelo usuário
            return $this->bearerToken;
        }

        if (!$forceRefresh) {
            // Cache na Memória Operacional
            if ($this->bearerToken) {
                return $this->bearerToken;
            }

            // Fallback: Cache em Arquivo Nativo (Com Prevenção de Acesso Público Hack)
            $cacheFile = $this->getCacheFilePath();
            if (file_exists($cacheFile)) {
                $content = file_get_contents($cacheFile);
                $content = str_replace("<?php die('Access Denied'); ?>\n", "", $content);
                $data = json_decode(trim($content), true);

                if ($data && isset($data['access_token']) && isset($data['expires_at'])) {
                    // Limite de segurança de 60 segundos p/ não disparar request com ele explodindo no voo
                    if (time() < ($data['expires_at'] - 60)) {
                        $this->bearerToken = $data['access_token'];
                        return $this->bearerToken;
                    }
                }
            }
        }

        // Necessidade de Gerar Token Oauth2 (Client Credentials)
        try {
            $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
            $response = $this->client->post($this->authUrl, [
                'headers' => [
                    'Authorization' => "Basic {$credentials}",
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            if (isset($data['access_token'])) {
                $this->bearerToken = $data['access_token'];
                // Tempo padrão de sobrevida de tokens: geralmente 3600 (1 hora)
                $expiresIn = (int) ($data['expires_in'] ?? 3600);

                $cacheData = [
                    'access_token' => $this->bearerToken,
                    'expires_at' => time() + $expiresIn
                ];

                $fileContent = "<?php die('Access Denied'); ?>\n" . json_encode($cacheData);
                file_put_contents($this->getCacheFilePath(), $fileContent);

                return $this->bearerToken;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public function consultCnpj(string $cnpj, bool $isRetry = false): ?CompanyData
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        try {
            $options = [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json'
                ]
            ];
            $response = $this->client->get($this->cnpjBaseUrl . preg_replace('/\D/', '', $cnpj), $options);
            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['ni'])) {
                return null;
            }

            $company = new CompanyData();
            $company->ni = $data['ni'] ?? null;
            $company->tipoEstabelecimento = $data['tipoEstabelecimento'] ?? null;
            $company->nomeEmpresarial = $data['nomeEmpresarial'] ?? null;
            $company->nomeFantasia = $data['nomeFantasia'] ?? null;
            $company->situacaoCadastral = $data['situacaoCadastral'] ?? null;
            $company->naturezaJuridica = $data['naturezaJuridica'] ?? null;
            $company->dataAbertura = $data['dataAbertura'] ?? null;
            $company->cnaePrincipal = $data['cnaePrincipal'] ?? null;
            $company->cnaeSecundarias = $data['cnaeSecundarias'] ?? null;
            $company->endereco = $data['endereco'] ?? null;
            $company->municipioJurisdicao = $data['municipioJurisdicao'] ?? null;
            $company->telefones = $data['telefones'] ?? null;
            $company->correioEletronico = $data['correioEletronico'] ?? null;
            $company->capitalSocial = isset($data['capitalSocial']) ? (float) $data['capitalSocial'] : null;
            $company->porte = $data['porte'] ?? null;
            $company->situacaoEspecial = $data['situacaoEspecial'] ?? null;
            $company->dataSituacaoEspecial = $data['dataSituacaoEspecial'] ?? null;
            $company->informacoesAdicionais = $data['informacoesAdicionais'] ?? null;
            $company->socios = $data['socios'] ?? null;

            return $company;

        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401 && !$isRetry && $this->consumerKey) {
                // Token expirou antes do previsto ou foi revogado do lado Serpro
                $this->getAccessToken(true); // Força um renew bypass cache
                return $this->consultCnpj($cnpj, true);
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function consultCpf(string $cpf, bool $isRetry = false): ?PersonData
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        try {
            $options = [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json'
                ]
            ];
            $response = $this->client->get($this->cpfBaseUrl . preg_replace('/\D/', '', $cpf), $options);
            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['ni'])) {
                return null;
            }

            $person = new PersonData();
            $person->ni = $data['ni'] ?? null;
            $person->nome = $data['nome'] ?? null;
            $person->situacao = $data['situacao'] ?? null;
            $person->nascimento = $data['nascimento'] ?? null;
            $person->dataInscricao = $data['dataInscricao'] ?? null;

            return $person;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401 && !$isRetry && $this->consumerKey) {
                // Token expirou ou corrompeu, força renovação do cache
                $this->getAccessToken(true);
                return $this->consultCpf($cpf, true);
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
