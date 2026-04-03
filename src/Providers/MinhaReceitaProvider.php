<?php

namespace BrazilTaxId\Providers;

use BrazilTaxId\Contracts\TaxIdProviderInterface;
use BrazilTaxId\Models\CompanyData;
use GuzzleHttp\Client;

class MinhaReceitaProvider implements TaxIdProviderInterface
{
    protected Client $client;
    protected ?string $token;
    protected string $baseUrl;

    public function __construct(?Client $client = null, ?string $token = null, string $baseUrl = 'https://minhareceita.org/')
    {
        $this->client = $client ?? new Client(['timeout' => 5.0]);
        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public function getName(): string
    {
        return 'MinhaReceita';
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
            $company->cnpj_raiz = substr(preg_replace('/\D/', '', $data['cnpj'] ?? ''), 0, 8);
            $company->razao_social = $data['razao_social'] ?? null;
            $company->capital_social = isset($data['capital_social']) ? (string)$data['capital_social'] : null;
            $company->natureza_juridica = isset($data['natureza_juridica']) ? ['descricao' => $data['natureza_juridica']] : null;
            $company->porte = isset($data['porte']) ? ['descricao' => $data['porte']] : null;
            
            if (!empty($data['qsa'])) {
                foreach($data['qsa'] as $socio) {
                    $company->socios[] = [
                        'nome' => $socio['nome_socio'] ?? null,
                        'qualificacao_socio' => ['descricao' => $socio['qualificacao_socio'] ?? null],
                        'cpf_cnpj_socio' => $socio['cnpj_cpf_do_socio'] ?? null
                    ];
                }
            }

            $company->estabelecimento = [
                'cnpj' => preg_replace('/\D/', '', $data['cnpj'] ?? ''),
                'nome_fantasia' => $data['nome_fantasia'] ?? null,
                'situacao_cadastral' => $data['descricao_situacao_cadastral'] ?? null,
                'logradouro' => $data['logradouro'] ?? null,
                'numero' => $data['numero'] ?? null,
                'complemento' => $data['complemento'] ?? null,
                'bairro' => $data['bairro'] ?? null,
                'cep' => preg_replace('/\D/', '', $data['cep'] ?? ''),
                'cidade' => ['nome' => $data['municipio'] ?? null],
                'estado' => ['sigla' => $data['uf'] ?? null]
            ];

            return $company;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
