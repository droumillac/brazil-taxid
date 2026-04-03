<?php

namespace BrazilTaxId;

use BrazilTaxId\Contracts\TaxIdProviderInterface;
use BrazilTaxId\Models\CompanyData;
use BrazilTaxId\Validators\CnpjValidator;
use BrazilTaxId\Providers\BrasilApiProvider;
use BrazilTaxId\Providers\MinhaReceitaProvider;
use BrazilTaxId\Providers\CnpjWsProvider;
use BrazilTaxId\Providers\ReceitaWsProvider;

class DocumentConsultant
{
    /** @var TaxIdProviderInterface[] */
    protected array $providers = [];

    /**
     * Permite injetar uma lista de provedores na ordem de prioridade desejada.
     * 
     * @param TaxIdProviderInterface[] $providers 
     */
    public function __construct(array $providers = [])
    {
        if (empty($providers)) {
            // Prioridade de contingência padrão sugerida pelo usuário indiretamente
            $this->providers = [
                new BrasilApiProvider(),
                new MinhaReceitaProvider(),
                new CnpjWsProvider(),
                new ReceitaWsProvider()
            ];
        } else {
            $this->providers = $providers;
        }
    }

    /**
     * Consulta um CNPJ na cadeia de provedores até obter sucesso.
     * Retorna o payload normalizado ou erro.
     */
    public function consultCnpj(string $cnpj): array
    {
        // 1. Validação prévia
        if (!CnpjValidator::isValid($cnpj)) {
            return [
                'success' => false,
                'message' => 'CNPJ inválido (verifique o formato ou dígito verificador, inclui regras de CNPJ alfanumérico).',
                'data' => null,
                'provider_used' => null
            ];
        }

        // 2. Consulta em fallback
        foreach ($this->providers as $provider) {
            $companyData = $provider->consultCnpj($cnpj);
            
            if ($companyData instanceof CompanyData && !empty($companyData->estabelecimento['cnpj'])) {
                return [
                    'success' => true,
                    'message' => 'Consulta realizada com sucesso.',
                    'data' => $companyData->toArray(),
                    'provider_used' => $provider->getName()
                ];
            }
        }

        // 3. Fallback esgotado
        return [
            'success' => false,
            'message' => 'Não foi possível consultar o CNPJ em nenhum dos provedores configurados (Fallback esgotado).',
            'data' => null,
            'provider_used' => null
        ];
    }
}
