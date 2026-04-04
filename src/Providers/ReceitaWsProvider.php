<?php

namespace BrazilTaxId\Providers;

use BrazilTaxId\Contracts\TaxIdProviderInterface;
use BrazilTaxId\Models\CompanyData;
use GuzzleHttp\Client;

class ReceitaWsProvider implements TaxIdProviderInterface
{
    protected Client $client;
    protected ?string $token;
    protected string $baseUrl;

    public function __construct(?Client $client = null, ?string $token = null, string $baseUrl = 'https://receitaws.com.br/v1/cnpj/')
    {
        $this->client = $client ?? new Client(['timeout' => 5.0]);
        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public function getName(): string
    {
        return 'ReceitaWS';
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

            if (!$data || isset($data['status']) && $data['status'] === 'ERROR') {
                return null;
            }

            $company = new CompanyData();
            $company->ni = preg_replace('/\D/', '', $data['cnpj'] ?? '');
            $company->tipoEstabelecimento = ($data['tipo'] ?? '') === 'MATRIZ' ? '1' : '2';
            $company->nomeEmpresarial = $data['nome'] ?? null;
            $company->nomeFantasia = $data['fantasia'] ?? null;
            $company->situacaoCadastral = [
                'codigo' => null,
                'data' => $data['data_situacao'] ?? null,
                'motivo' => $data['motivo_situacao'] ?? null
            ];
            $company->naturezaJuridica = [
                'codigo' => null,
                'descricao' => $data['natureza_juridica'] ?? null
            ];
            $company->dataAbertura = $data['abertura'] ?? null;
            
            $company->cnaePrincipal = [];
            $cnaesSecundarias = [];
            if (!empty($data['atividade_principal'][0])) {
                $company->cnaePrincipal = [
                    'codigo' => preg_replace('/\D/', '', $data['atividade_principal'][0]['code'] ?? ''),
                    'descricao' => $data['atividade_principal'][0]['text'] ?? null
                ];
            }
            if (!empty($data['atividades_secundarias'])) {
                foreach ($data['atividades_secundarias'] as $sec) {
                    $cnaesSecundarias[] = [
                        'codigo' => preg_replace('/\D/', '', $sec['code'] ?? ''),
                        'descricao' => $sec['text'] ?? null
                    ];
                }
            }
            $company->cnaeSecundarias = $cnaesSecundarias;

            $company->endereco = [
                'tipoLogradouro' => null,
                'logradouro' => $data['logradouro'] ?? null,
                'numero' => $data['numero'] ?? null,
                'complemento' => $data['complemento'] ?? null,
                'cep' => preg_replace('/\D/', '', $data['cep'] ?? ''),
                'bairro' => $data['bairro'] ?? null,
                'municipio' => [
                    'codigo' => null,
                    'descricao' => $data['municipio'] ?? null
                ],
                'uf' => $data['uf'] ?? null,
                'pais' => null
            ];

            $telefones = [];
            if (!empty($data['telefone'])) {
                $tels = explode(',', $data['telefone']);
                foreach($tels as $tel) {
                    $telVal = trim(preg_replace('/\D/', '', $tel));
                    if(strlen($telVal) >= 10) {
                        $telefones[] = ['ddd' => substr($telVal, 0, 2), 'numero' => substr($telVal, 2)];
                    }
                }
            }
            $company->telefones = $telefones;
            $company->correioEletronico = $data['email'] ?? null;
            $company->capitalSocial = isset($data['capital_social']) ? (float)$data['capital_social'] : null;
            $company->porte = $data['porte'] ?? null;

            $socios = [];
            if (!empty($data['qsa'])) {
                foreach($data['qsa'] as $socio) {
                    $socios[] = [
                        'tipoSocio' => null,
                        'cpf' => null,
                        'nome' => $socio['nome'] ?? null,
                        'qualificacao' => $socio['qual'] ?? null,
                        'dataInclusao' => null,
                        'pais' => null,
                        'representanteLegal' => [
                            'cpf' => null,
                            'nome' => $socio['nome_rep_legal'] ?? null,
                            'qualificacao' => $socio['qual_rep_legal'] ?? null
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
