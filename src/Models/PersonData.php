<?php

namespace BrazilTaxId\Models;

class PersonData
{
    public ?string $ni = null;
    public ?string $nome = null;
    public ?array $situacao = null;
    public ?string $nascimento = null;
    public ?string $dataInscricao = null;

    public function toArray(): array
    {
        return [
            'ni' => $this->ni,
            'nome' => $this->nome,
            'situacao' => $this->situacao,
            'nascimento' => $this->nascimento,
            'dataInscricao' => $this->dataInscricao
        ];
    }
}
