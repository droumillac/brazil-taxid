<?php

namespace BrazilTaxId\Models;

class CompanyData
{
    public ?string $cnpj_raiz = null;
    public ?string $razao_social = null;
    public ?string $capital_social = null;
    public ?string $responsavel_federativo = null;
    public ?string $atualizado_em = null;
    public ?array $porte = null;
    public ?array $natureza_juridica = null;
    public ?array $qualificacao_do_responsavel = null;
    public array $socios = [];
    public ?array $simples = null;
    public ?array $estabelecimento = null;

    public function toArray(): array
    {
        return [
            'cnpj_raiz' => $this->cnpj_raiz,
            'razao_social' => $this->razao_social,
            'capital_social' => $this->capital_social,
            'responsavel_federativo' => $this->responsavel_federativo,
            'atualizado_em' => $this->atualizado_em,
            'porte' => $this->porte,
            'natureza_juridica' => $this->natureza_juridica,
            'qualificacao_do_responsavel' => $this->qualificacao_do_responsavel,
            'socios' => $this->socios,
            'simples' => $this->simples,
            'estabelecimento' => $this->estabelecimento,
        ];
    }
}
