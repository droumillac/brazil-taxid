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
            $cnpjNumeros = preg_replace('/[^a-zA-Z0-9]/', '', $cnpj);
            
            $options = [];
            if ($this->token) {
                $options['headers'] = ['Authorization' => "Bearer {$this->token}"];
            }

            $response = $this->client->get($this->baseUrl . $cnpjNumeros, $options);
            $data = json_decode($response->getBody(), true);

            if (!$data || !isset($data['estabelecimento'])) {
                return null;
            }

            $company = new CompanyData();
            $company->cnpj_raiz = $data['cnpj_raiz'] ?? null;
            $company->razao_social = $data['razao_social'] ?? null;
            $company->capital_social = $data['capital_social'] ?? null;
            $company->responsavel_federativo = $data['responsavel_federativo'] ?? null;
            $company->atualizado_em = $data['atualizado_em'] ?? null;
            $company->porte = $data['porte'] ?? null;
            $company->natureza_juridica = $data['natureza_juridica'] ?? null;
            $company->qualificacao_do_responsavel = $data['qualificacao_do_responsavel'] ?? null;
            $company->socios = $data['socios'] ?? [];
            $company->simples = $data['simples'] ?? null;
            $company->estabelecimento = $data['estabelecimento'] ?? null;

            return $company;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
