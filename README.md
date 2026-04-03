# droumillac/brazil-taxid (CPF & CNPJ Validator/API Consultant)

Uma biblioteca PHP robusta focada em ecossistemas modernos do Brasil. Além de realizar a **validação híbrida matemática de CNPJs** (incluindo o novo padrão alfanumérico estipulado para Julho/2026), possui uma inteligência de **fallback (contingência)** nativa para realizar consultas cadastrais em diferentes APIs públicas, garantindo alta disponibilidade e a entrega de um JSON padronizado.

## ✨ Funcionalidades Principais

- **Validação de CNPJ Híbrido:** Suporte integral ao padrão de CNPJ alfanumérico que entra em vigor em 2026 utilizando cálculos avançados (Tabela ASCII).
- **Validação Tradicional de CPF**.
- **Alta Disponibilidade (Fallback System):** Se uma API estiver inoperante (HTTP Error, Rate Limit, etc), a biblioteca consulta automaticamente a próxima disponível na fila.
- **Payload Normalizado (DTO):** Chega de se deparar com `"situacao_cadastral"` em um serviço e `"descricao_situacao_cadastral"` em outro. O retorno dos dados da empresa é unificado em um Data Transfer Object de fácil controle (`CompanyData`).
- **Configurável:** Você define qual será a sua primeira opção de resposta e quais os pesos de contingência.

## 🚀 Provedores de API Suportados

- **[BrasilAPI](https://brasilapi.com.br/)** *(Prioridade padrão 1)*
- **[MinhaReceita](https://minhareceita.org/)** *(Prioridade padrão 2)*
- **[CnpjWS](https://cnpj.ws/)** *(Prioridade padrão 3)*
- **[ReceitaWS](https://receitaws.com.br/)** *(Prioridade padrão 4 - Restrito, serve bem para fallback)*

## 📦 Instalação

Como o pacote usa PSR-4 e GuzzleHttp para as requisições, você pode integrá-lo ao seu ambiente adicionando através do composer:

```bash
composer require droumillac/brazil-taxid
```

## 🛠️ Como Utilizar

### Validação de Documentos

Para checar a integridade de dígitos verificadores e formato:

```php
use BrazilTaxId\Validators\CnpjValidator;
use BrazilTaxId\Validators\CpfValidator;

// Novo cenário Alfanumérico!
$validoStr = CnpjValidator::isValid('12ABC34501DE35'); // bool(true/false)

$validoCpf = CpfValidator::isValid('111.222.333-44'); // bool(true/false)
```

### Consultando CNPJ (Modo Padrão)

Este é o modo prático, instancie o `DocumentConsultant` sem parâmetros e deixe-o rodar a sequência recomendada.

```php
use BrazilTaxId\DocumentConsultant;

$consultant = new DocumentConsultant(); 
$resposta = $consultant->consultCnpj('00.000.000/0001-91');

if ($resposta['success']) {
    print_r($resposta['data']); 
    // Vai imprimir array padronizado:
    // [ 'cnpj' => ..., 'razao_social' => ..., 'cep' => ... ]
} else {
    echo $resposta['message'];
}

// Para ver qual provedor funcionou:
echo "Fornecido por: " . $resposta['provider_used'];
```

### Alterando as Prioridades das APIs

Você possui um plano VIP no CnpjWS ou apenas prefere a MinhaReceita? Injete a sua própria ordem de provedores construtor da classe. O sistema respeitará essa ordem de busca!

```php
use BrazilTaxId\DocumentConsultant;
use BrazilTaxId\Providers\MinhaReceitaProvider;
use BrazilTaxId\Providers\BrasilApiProvider;

// Define a ordem customizada!
$customConsultant = new DocumentConsultant([
    new MinhaReceitaProvider(), // Vai disparar aqui PRIMEIRO!
    new BrasilApiProvider()     // Se a MinhaReceita falar, tenta nessa!
]);

$resultado = $customConsultant->consultCnpj('06.990.590/0001-23');
```

### Usando Planos Pagos / Autenticação

Se você assina um plano pago (como o da *ReceitaWS* ou *CnpjWS Comercial*), as URLs de requisição frequentemente mudam e passam a exigir Tokens. A biblioteca já está preparada para isso! 
Basta instanciar a API desejada passando o seu `token` no segundo parâmetro e a `baseUrl` comercial no terceiro parâmetro:

```php
use BrazilTaxId\DocumentConsultant;
use BrazilTaxId\Providers\CnpjWsProvider;
use BrazilTaxId\Providers\ReceitaWsProvider;

// Exemplo usando plano comercial:
$meuCnpjWsComercial = new CnpjWsProvider(
    null,                                // 1º param: Guzzle Client (null = padrão)
    'SEU_TOKEN_DE_ASSINATURA',           // 2º param: Token (Enviado como Bearer Token Header)
    'https://comercial.cnpj.ws/cnpj/'    // 3º param: URL Base comercial
);

$minhaReceitaWsComercial = new ReceitaWsProvider(
    null,
    'SEU_TOKEN_RECEITA',
    'https://api.receitaws.com.br/v1/cnpj/'
);

$consultantVIP = new DocumentConsultant([
    $meuCnpjWsComercial,
    $minhaReceitaWsComercial
]);

$resultado = $consultantVIP->consultCnpj('06.990.590/0001-23');
```

## 🗃️ Estrutura do DTO de Retorno

Sempre que a key `success` for `true`, o escopo `data` terá essa composição garantida e aprofundada por contrato de Interface (Mesmo que o serviço acionado não suporte aquele campo nativamente, ele será enviado como null, padronizando a segurança da sua aplicação).

```json
{
  "cnpj_raiz": "00000000",
  "razao_social": "BANCO DO BRASIL SA",
  "capital_social": "90000000000.00",
  "responsavel_federativo": null,
  "atualizado_em": null,
  "porte": {
    "descricao": "Demais"
  },
  "natureza_juridica": {
    "descricao": "Sociedade de Economia Mista"
  },
  "qualificacao_do_responsavel": null,
  "socios": [
    {
      "nome": "MINISTERIO DA ECONOMIA",
      "qualificacao_socio": {
        "descricao": "Acionista"
      },
      "cpf_cnpj_socio": "00394460000141"
    }
  ],
  "simples": null,
  "estabelecimento": {
    "cnpj": "00000000000191",
    "nome_fantasia": "DIRECAO GERAL",
    "situacao_cadastral": "Ativa",
    "logradouro": "SAUN QUADRA 5 LOTE B TORRES I, II E III",
    "numero": "S/N",
    "complemento": "EDIF BANCO DO BRASIL",
    "bairro": "ASA NORTE",
    "cep": "70040912",
    "cidade": {
      "nome": "BRASILIA"
    },
    "estado": {
      "sigla": "DF"
    }
  }
}
```

---
*Requer PHP 8.1 ou superior e GuzzleHTTP.*
