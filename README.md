# CPF & CNPJ Validator/API Consultant

Uma biblioteca PHP robusta focada em ecossistemas modernos do Brasil. Além de realizar a **validação híbrida matemática de CNPJs** (incluindo o novo padrão alfanumérico estipulado para Julho/2026) e **CPFs**, possui uma inteligência de **fallback (contingência)** nativa para realizar consultas cadastrais em diferentes APIs públicas, garantindo alta disponibilidade e a entrega de um JSON padronizado.

## ✨ Funcionalidades Principais

- **Validação de CNPJ Híbrido:** Suporte integral ao padrão de CNPJ alfanumérico que entra em vigor em 2026 utilizando cálculos avançados (Tabela ASCII).
- **Validação de CPF.**
- **Alta Disponibilidade (Fallback System):** Se uma API estiver inoperante (HTTP Error, Rate Limit, etc), a biblioteca consulta automaticamente a próxima disponível na fila.
- **Payload Normalizado (DTO Padrão SERPRO):** O retorno dos dados da empresa ou pessoa física é perfeitamente unificado e inspirado na arquitetura da API do SERPRO, em Data Transfer Objects de fácil controle (`CompanyData` para CNPJ e `PersonData` para CPF).
- **Configurável:** Você define qual será a sua primeira opção de resposta e quais os pesos de contingência.

## 🚀 Provedores de API Suportados

**Para CNPJ:**
- **[BrasilAPI](https://brasilapi.com.br/)** *(Prioridade padrão 1)*
- **[MinhaReceita](https://minhareceita.org/)** *(Prioridade padrão 2)*
- **[CnpjWS](https://cnpj.ws/)** *(Prioridade padrão 3)*
- **[ReceitaWS](https://receitaws.com.br/)** *(Prioridade padrão 4 - Restrito)*
- **Serpro (Oficial)** *(Exige Token)*

**Para CPF:**
- **Serpro (Oficial)** *(Exige Token)*

## 📦 Instalação

Como o pacote usa PSR-4 e GuzzleHttp para as requisições, adicione-o via composer:

```bash
composer require droumillac/brazil-taxid
```

## 🛠️ Como Utilizar

### Validação de Documentos

```php
use BrazilTaxId\Validators\CnpjValidator;
use BrazilTaxId\Validators\CpfValidator;

// Novo cenário Alfanumérico!
$validoStr = CnpjValidator::isValid('12ABC34501DE35'); // bool(true/false)

$validoCpf = CpfValidator::isValid('111.222.333-44'); // bool(true/false)
```

### Consultando CNPJ ou CPF (Modo Padrão Falback)

```php
use BrazilTaxId\DocumentConsultant;

// Se não passar nada, ele usará a lista gratuita de CNPJ (BrasilAPI, MinhaReceita, CnpjWS, ReceitaWS)
$consultant = new DocumentConsultant(); 
$respostaCnpj = $consultant->consultCnpj('00.000.000/0001-91');
$respostaCpf = $consultant->consultCpf('111.222.333-44'); // Vai falhar se não houver provedores de CPF configurados

if ($respostaCnpj['success']) {
    print_r($respostaCnpj['data']); // Array padronizado padrão Serpro!
} else {
    echo $respostaCnpj['message'];
}

// Para ver qual provedor funcionou:
echo "Fornecido por: " . json_encode($respostaCnpj['message']);
```

### Alterando as Prioridades das APIs (Injetando Provedores API)

Você possui a liberdade de injetar a classe provedora preterida passando instâncias no construtor. O primeiro array é dedicado a provedores de CNPJ, e o segundo a provedores de CPF.

```php
use BrazilTaxId\DocumentConsultant;
use BrazilTaxId\Providers\MinhaReceitaProvider;
use BrazilTaxId\Providers\BrasilApiProvider;
use BrazilTaxId\Providers\SerproProvider;

// Define a ordem customizada!
// O Serpro emitirá automaticamente um Token Oauth2 e fará o Cache Inteligente disso 
// na temporária do Sistema Operacional de forma 100% autônoma para economizar suas cotas.
$customConsultant = new DocumentConsultant(
    [ // Array de CNPJs
        new SerproProvider('SUA_CONSUMER_KEY', 'SEU_CONSUMER_SECRET'), // Tenta o SERPRO Oficial primeiro 
        new MinhaReceitaProvider(), // Cai pra Minha Receita se falhar
    ],
    [ // Array de CPFs
        new SerproProvider('SUA_CONSUMER_KEY', 'SEU_CONSUMER_SECRET')
    ]
);

$resultado = $customConsultant->consultCnpj('06.990.590/0001-23');
$resultadoCpf = $customConsultant->consultCpf('111.222.333-44');
```

## 🗃️ Estrutura do DTO de Retorno (Baseado na Identidade SERPRO)

Sempre que a key `success` for `true`, o escopo `data` terá a composição espelhada do SERPRO para altíssima confiabilidade e adoção governamental, independentemente de qual API (CnpjWS, BrasilAPI) retornou os dados.

### Exemplo do Data retornado de CNPJ (`CompanyData` DTO)
```json
{
    "ni": "00000000000191",
    "tipoEstabelecimento": "1",
    "nomeEmpresarial": "BANCO DO BRASIL SA",
    "nomeFantasia": "DIRECAO GERAL",
    "situacaoCadastral": {
        "codigo": "2",
        "data": "2005-11-03",
        "motivo": ""
    },
    "naturezaJuridica": {
        "codigo": "2038",
        "descricao": "Sociedade de Economia Mista"
    },
    ...
}
```

### Exemplo do Data retornado de CPF (`PersonData` DTO)
```json
{
    "ni": "00000000001",
    "nome": "JOAO DAS COUVES",
    "situacao": {
        "codigo": "0",
        "descricao": "REGULAR"
    },
    "nascimento": "29021999",
    "dataInscricao": "04072000"
}
```

---
*Requer PHP 8.1 ou superior e GuzzleHTTP.*
