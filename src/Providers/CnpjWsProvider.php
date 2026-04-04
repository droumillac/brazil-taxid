<?php

namespace BrazilTaxId\Providers;

use BrazilTaxId\Contracts\TaxIdProviderInterface;
use BrazilTaxId\Models\CompanyData;
use GuzzleHttp\Client;

class CnpjWsProvider implements TaxIdProviderInterface
{
    protected Client $client;
    protected ?string $token;
    protected string $baseUrl;

    public function __construct(?Client $client = null, ?string $token = null, string $baseUrl = 'https://publica.cnpj.ws/cnpj/')
    {
        $this->client = $client ?? new Client(['timeout' => 5.0]);
        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public function getName(): string
    {
        return 'CnpjWs';
    }

    public function consultCnpj(string $cnpj): ?CompanyData
    {
        try {
            $options = [];
            if ($this->token) {
                $options['headers'] = ['Authorization' => "Bearer {$this->token}"];
            }
            $response = $this->client->get($this->baseUrl . preg_replace('/\D/', '', $cnpj), $options);
            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['cnpj_raiz'])) {
                return null;
            }

            $company = new CompanyData();
            $company->ni = $data['estabelecimento']['cnpj'] ?? null;
            $company->tipoEstabelecimento = $data['estabelecimento']['tipo'] ?? null;
            $company->nomeEmpresarial = $data['razao_social'] ?? null;
            $company->nomeFantasia = $data['estabelecimento']['nome_fantasia'] ?? null;
            $company->situacaoCadastral = [
                'codigo' => $data['estabelecimento']['situacao_cadastral'] ?? null,
                'data' => $data['estabelecimento']['data_situacao_cadastral'] ?? null,
                'motivo' => $data['estabelecimento']['motivo_situacao_cadastral'] ?? null
            ];
            $company->naturezaJuridica = [
                'codigo' => $data['natureza_juridica']['id'] ?? null,
                'descricao' => $data['natureza_juridica']['descricao'] ?? null
            ];
            $company->dataAbertura = $data['estabelecimento']['data_inicio_atividade'] ?? null;
            $company->cnaePrincipal = [
                'codigo' => $data['estabelecimento']['atividade_principal']['id'] ?? null,
                'descricao' => $data['estabelecimento']['atividade_principal']['descricao'] ?? null
            ];
            
            $cnaesSecundarias = [];
            if (!empty($data['estabelecimento']['atividades_secundarias'])) {
                foreach ($data['estabelecimento']['atividades_secundarias'] as $sec) {
                    $cnaesSecundarias[] = [
                        'codigo' => $sec['id'] ?? null,
                        'descricao' => $sec['descricao'] ?? null
                    ];
                }
            }
            $company->cnaeSecundarias = $cnaesSecundarias;

            $company->endereco = [
                'tipoLogradouro' => $data['estabelecimento']['tipo_logradouro'] ?? null,
                'logradouro' => $data['estabelecimento']['logradouro'] ?? null,
                'numero' => $data['estabelecimento']['numero'] ?? null,
                'complemento' => $data['estabelecimento']['complemento'] ?? null,
                'cep' => $data['estabelecimento']['cep'] ?? null,
                'bairro' => $data['estabelecimento']['bairro'] ?? null,
                'municipio' => [
                    'codigo' => $data['estabelecimento']['cidade']['ibge_id'] ?? null,
                    'descricao' => $data['estabelecimento']['cidade']['nome'] ?? null
                ],
                'uf' => $data['estabelecimento']['estado']['sigla'] ?? null,
                'pais' => [
                    'codigo' => $data['estabelecimento']['pais']['id'] ?? null,
                    'descricao' => $data['estabelecimento']['pais']['nome'] ?? null
                ]
            ];

            $telefones = [];
            if (!empty($data['estabelecimento']['ddd1']) && !empty($data['estabelecimento']['telefone1'])) {
                $telefones[] = ['ddd' => $data['estabelecimento']['ddd1'], 'numero' => $data['estabelecimento']['telefone1']];
            }
            if (!empty($data['estabelecimento']['ddd2']) && !empty($data['estabelecimento']['telefone2'])) {
                $telefones[] = ['ddd' => $data['estabelecimento']['ddd2'], 'numero' => $data['estabelecimento']['telefone2']];
            }
            $company->telefones = $telefones;
            $company->correioEletronico = $data['estabelecimento']['email'] ?? null;
            $company->capitalSocial = isset($data['capital_social']) ? (float)$data['capital_social'] : null;
            $company->porte = $data['porte']['id'] ?? null;
            
            $socios = [];
            if (!empty($data['socios'])) {
                foreach ($data['socios'] as $soc) {
                    $socios[] = [
                        'tipoSocio' => $soc['tipo'] ?? null,
                        'cpf' => $soc['cpf_cnpj_socio'] ?? null,
                        'nome' => $soc['nome'] ?? null,
                        'qualificacao' => $soc['qualificacao_socio']['id'] ?? null,
                        'dataInclusao' => $soc['data_entrada'] ?? null,
                        'pais' => [
                            'codigo' => $soc['pais_id'] ?? null,
                            'descricao' => null
                        ],
                        'representanteLegal' => [
                            'cpf' => $soc['cpf_representante_legal'] ?? null,
                            'nome' => $soc['nome_representante'] ?? null,
                            'qualificacao' => $soc['qualificacao_representante_legal']['id'] ?? null
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
