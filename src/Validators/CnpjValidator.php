<?php

namespace BrazilTaxId\Validators;

class CnpjValidator
{
    private const TAMANHO_CNPJ_SEM_DV = 12;
    private const PESOS_DV = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public static function isValid(string $cnpj): bool
    {
        $cnpjLimpo = preg_replace('/[.\-\/]/', '', $cnpj);
        
        if (preg_match('/[^A-Z\d]/i', $cnpjLimpo)) {
            return false;
        }

        $cnpjLimpo = strtoupper($cnpjLimpo);

        if (strlen($cnpjLimpo) !== 14 || $cnpjLimpo === '00000000000000') {
            return false;
        }

        $cnpjSemDv = substr($cnpjLimpo, 0, self::TAMANHO_CNPJ_SEM_DV);
        $dvInformado = substr($cnpjLimpo, self::TAMANHO_CNPJ_SEM_DV);

        try {
            $dvCalculado = self::calculaDv($cnpjSemDv);
            return $dvInformado === $dvCalculado;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function calculaDv(string $cnpjSemDv): string
    {
        if (preg_match('/[^A-Z\d]/i', $cnpjSemDv) || strlen($cnpjSemDv) !== self::TAMANHO_CNPJ_SEM_DV || $cnpjSemDv === '000000000000') {
            throw new \Exception("Não é possível calcular o DV pois o formato fornecido é inválido");
        }

        $cnpjSemDv = strtoupper($cnpjSemDv);
        $somatorioDV1 = 0;
        $somatorioDV2 = 0;

        for ($i = 0; $i < self::TAMANHO_CNPJ_SEM_DV; $i++) {
            $char = $cnpjSemDv[$i];
            $asciiDigito = ord($char) - 48;
            
            $somatorioDV1 += $asciiDigito * self::PESOS_DV[$i + 1];
            $somatorioDV2 += $asciiDigito * self::PESOS_DV[$i];
        }

        $dv1 = ($somatorioDV1 % 11 < 2) ? 0 : 11 - ($somatorioDV1 % 11);
        $somatorioDV2 += $dv1 * self::PESOS_DV[self::TAMANHO_CNPJ_SEM_DV];
        
        $dv2 = ($somatorioDV2 % 11 < 2) ? 0 : 11 - ($somatorioDV2 % 11);

        return "{$dv1}{$dv2}";
    }
}
