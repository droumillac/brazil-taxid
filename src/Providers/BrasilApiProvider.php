<?php

namespace BrazilTaxId\Providers;

use BrazilTaxId\Contracts\TaxIdProviderInterface;
use BrazilTaxId\Models\CompanyData;
use GuzzleHttp\Client;

class BrasilApiProvider implements TaxIdProviderInterface
{
    protected Client $client;
    protected ?string $token;
    protected string $baseUrl;

    public function __construct(?Client $client = null, ?string $token = null, string $baseUrl = 'https://brasilapi.com.br/api/cnpj/v1/')
    {
        $this->client = $client ?? new Client(['timeout' => 5.0]);
        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public function getName(): string
    {
        return 'BrasilAPI';
    }

    public function consultCnpj(string $cnpj): ?CompanyData
    {
        try {
            $cnpjNumeros = preg_replace('/[^a-zA-Z0-9]/', '', $cnpj);
            
            $options = [];
            if ($this->token) {
                $options['headers'] = ['Authorization' => "Bearer {$this->token}"];
            }

            $response = $this->client->get($this->baseUrl . $cnpjNumeros, $options);
            $data = json_decode($response->getBody(), true);

            if (!$data || !isset($data['cnpj'])) {
                return null;
            }

            $company = new CompanyData();
            $company->ni = preg_replace('/\D/', '', $data['cnpj'] ?? '');
            $company->tipoEstabelecimento = ($data['identificador_matriz_filial'] ?? 1) == 1 ? '1' : '2';
            $company->nomeEmpresarial = $data['razao_social'] ?? null;
            $company->nomeFantasia = $data['nome_fantasia'] ?? null;
            $company->situacaoCadastral = [
                'codigo' => $data['situacao_cadastral'] ?? null,
                'data' => $data['data_situacao_cadastral'] ?? null,
                'motivo' => $data['motivo_situacao_cadastral'] ?? null
            ];
            $company->naturezaJuridica = [
                'codigo' => $data['codigo_natureza_juridica'] ?? null,
                'descricao' => $data['natureza_juridica'] ?? null
            ];
            $company->dataAbertura = $data['data_inicio_atividade'] ?? null;
            $company->cnaePrincipal = [
                'codigo' => $data['cnae_fiscal'] ?? null,
                'descricao' => $data['cnae_fiscal_descricao'] ?? null
            ];
            $cnaesSecundarias = [];
            if (!empty($data['cnaes_secundarios'])) {
                foreach ($data['cnaes_secundarios'] as $sec) {
                    $cnaesSecundarias[] = [
                        'codigo' => $sec['codigo'] ?? null,
                        'descricao' => $sec['descricao'] ?? null
                    ];
                }
            }
            $company->cnaeSecundarias = $cnaesSecundarias;

            $company->endereco = [
                'tipoLogradouro' => $data['descricao_tipo_de_logradouro'] ?? null,
                'logradouro' => $data['logradouro'] ?? null,
                'numero' => $data['numero'] ?? null,
                'complemento' => $data['complemento'] ?? null,
                'cep' => preg_replace('/\D/', '', $data['cep'] ?? ''),
                'bairro' => $data['bairro'] ?? null,
                'municipio' => [
                    'codigo' => $data['codigo_municipio'] ?? null,
                    'descricao' => $data['municipio'] ?? null
                ],
                'uf' => $data['uf'] ?? null,
                'pais' => null
            ];

            $telefones = [];
            if (!empty($data['ddd_telefone_1'])) {
                $tel = preg_replace('/\D/', '', $data['ddd_telefone_1']);
                if(strlen($tel) >= 10) {
                    $telefones[] = ['ddd' => substr($tel, 0, 2), 'numero' => substr($tel, 2)];
                }
            }
            if (!empty($data['ddd_telefone_2'])) {
                $tel = preg_replace('/\D/', '', $data['ddd_telefone_2']);
                if(strlen($tel) >= 10) {
                    $telefones[] = ['ddd' => substr($tel, 0, 2), 'numero' => substr($tel, 2)];
                }
            }
            $company->telefones = $telefones;
            $company->correioEletronico = $data['email'] ?? null;
            $company->capitalSocial = isset($data['capital_social']) ? (float)$data['capital_social'] : null;
            $company->porte = $data['codigo_porte'] ?? null;

            $socios = [];
            if (!empty($data['qsa'])) {
                foreach($data['qsa'] as $soc) {
                    $socios[] = [
                        'tipoSocio' => $soc['identificador_de_socio'] ?? null,
                        'cpf' => $soc['cnpj_cpf_do_socio'] ?? null,
                        'nome' => $soc['nome_socio'] ?? null,
                        'qualificacao' => $soc['codigo_qualificacao_socio'] ?? null,
                        'dataInclusao' => $soc['data_entrada_sociedade'] ?? null,
                        'pais' => null,
                        'representanteLegal' => [
                            'cpf' => $soc['cpf_representante_legal'] ?? null,
                            'nome' => $soc['nome_representante_legal'] ?? null,
                            'qualificacao' => $soc['codigo_qualificacao_representante_legal'] ?? null
                        ]
                    ];
                }
            }
            $company->socios = $socios;

            return $company;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
