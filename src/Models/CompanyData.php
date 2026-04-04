<?php

namespace BrazilTaxId\Models;

class CompanyData
{
    public ?string $ni = null;
    public ?string $tipoEstabelecimento = null;
    public ?string $nomeEmpresarial = null;
    public ?string $nomeFantasia = null;
    public ?array $situacaoCadastral = null;
    public ?array $naturezaJuridica = null;
    public ?string $dataAbertura = null;
    public ?array $cnaePrincipal = null;
    public ?array $cnaeSecundarias = null;
    public ?array $endereco = null;
    public ?array $municipioJurisdicao = null;
    public ?array $telefones = null;
    public ?string $correioEletronico = null;
    public ?float $capitalSocial = null;
    public ?string $porte = null;
    public ?string $situacaoEspecial = null;
    public ?string $dataSituacaoEspecial = null;
    public ?array $informacoesAdicionais = null;
    public ?array $socios = null;

    public function toArray(): array
    {
        return [
            'ni' => $this->ni,
            'tipoEstabelecimento' => $this->tipoEstabelecimento,
            'nomeEmpresarial' => $this->nomeEmpresarial,
            'nomeFantasia' => $this->nomeFantasia,
            'situacaoCadastral' => $this->situacaoCadastral,
            'naturezaJuridica' => $this->naturezaJuridica,
            'dataAbertura' => $this->dataAbertura,
            'cnaePrincipal' => $this->cnaePrincipal,
            'cnaeSecundarias' => $this->cnaeSecundarias,
            'endereco' => $this->endereco,
            'municipioJurisdicao' => $this->municipioJurisdicao,
            'telefones' => $this->telefones,
            'correioEletronico' => $this->correioEletronico,
            'capitalSocial' => $this->capitalSocial,
            'porte' => $this->porte,
            'situacaoEspecial' => $this->situacaoEspecial,
            'dataSituacaoEspecial' => $this->dataSituacaoEspecial,
            'informacoesAdicionais' => $this->informacoesAdicionais,
            'socios' => $this->socios
        ];
    }
}
