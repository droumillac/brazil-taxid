<?php

namespace BrazilTaxId;

use BrazilTaxId\Contracts\TaxIdProviderInterface;
use BrazilTaxId\Contracts\CpfProviderInterface;
use BrazilTaxId\Models\CompanyData;
use BrazilTaxId\Models\PersonData;
use BrazilTaxId\Validators\CnpjValidator;
use BrazilTaxId\Validators\CpfValidator;
use BrazilTaxId\Providers\BrasilApiProvider;
use BrazilTaxId\Providers\MinhaReceitaProvider;
use BrazilTaxId\Providers\CnpjWsProvider;
use BrazilTaxId\Providers\ReceitaWsProvider;

class DocumentConsultant
{
    /** @var TaxIdProviderInterface[] */
    protected array $cnpjProviders = [];

    /** @var CpfProviderInterface[] */
    protected array $cpfProviders = [];

    /**
     * Permite injetar uma lista de provedores na ordem de prioridade desejada.
     * 
     * @param TaxIdProviderInterface[] $cnpjProviders
     * @param CpfProviderInterface[] $cpfProviders
     */
    public function __construct(array $cnpjProviders = [], array $cpfProviders = [])
    {
        if (empty($cnpjProviders)) {
            // Prioridade de contingência padrão sugerida pelo usuário indiretamente
            $this->cnpjProviders = [
                new BrasilApiProvider(),
                new MinhaReceitaProvider(),
                new CnpjWsProvider(),
                new ReceitaWsProvider()
            ];
        } else {
            foreach ($cnpjProviders as $provider) {
                $this->addCnpjProvider($provider);
            }
        }

        foreach ($cpfProviders as $provider) {
            $this->addCpfProvider($provider);
        }
    }

    public function addProvider(TaxIdProviderInterface $provider): void
    {
        $this->addCnpjProvider($provider);
    }

    public function addCnpjProvider(TaxIdProviderInterface $provider): void
    {
        $this->cnpjProviders[] = $provider;
    }

    public function addCpfProvider(CpfProviderInterface $provider): void
    {
        $this->cpfProviders[] = $provider;
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
                'data' => null
            ];
        }

        // 2. Fallback de Provedores
        foreach ($this->cnpjProviders as $provider) {
            try {
                $result = $provider->consultCnpj($cnpj);
                // Valida se o provedor retornou algo preenchido antes de fechar o loop
                if ($result && !empty($result->ni)) {
                    return [
                        'success' => true,
                        'message' => 'Consulta realizada com sucesso via ' . $provider->getName(),
                        'data' => $result->toArray()
                    ];
                }
            } catch (\Exception $e) {
                // Aqui você pode logar a exceção se desejar
                // Log::warning("Provedor {$provider->getName()} falhou.");
                continue;
            }
        }

        // 3. Resposta de contingência esgotada
        return [
            'success' => false,
            'message' => 'Nenhum provedor conseguiu retornar os dados para este CNPJ. É possível que o CNPJ não exista ou as APIs estejam indisponíveis.',
            'data' => null
        ];
    }

    /**
     * Consulta um CPF na cadeia de provedores até obter sucesso.
     * Retorna o payload normalizado ou erro.
     */
    public function consultCpf(string $cpf): array
    {
        // 1. Validação prévia
        if (!CpfValidator::isValid($cpf)) {
            return [
                'success' => false,
                'message' => 'CPF inválido (verifique o formato ou dígito verificador).',
                'data' => null
            ];
        }

        // 2. Fallback de Provedores
        foreach ($this->cpfProviders as $provider) {
            try {
                $result = $provider->consultCpf($cpf);
                if ($result && !empty($result->ni)) {
                    return [
                        'success' => true,
                        'message' => 'Consulta realizada com sucesso via ' . $provider->getName(),
                        'data' => $result->toArray()
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 3. Resposta de contingência esgotada
        return [
            'success' => false,
            'message' => 'Nenhum provedor conseguiu retornar os dados para este CPF. É possível que o CPF não exista ou as APIs estejam indisponíveis.',
            'data' => null
        ];
    }
}
