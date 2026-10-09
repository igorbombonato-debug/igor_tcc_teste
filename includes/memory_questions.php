<?php

function normalizar_materia_memoria(string $nome): string
{
    return strtolower(strtr($nome, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
        'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a',
        'É' => 'e', 'Ê' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o',
        'Ú' => 'u', 'Ç' => 'c',
        'º' => 'o', 'ª' => 'a',
    ]));
}

function simplificar_fracao_memoria(int $numerador, int $denominador): string
{
    $a = abs($numerador);
    $b = abs($denominador);
    while ($b !== 0) {
        [$a, $b] = [$b, $a % $b];
    }

    $divisor = max($a, 1);
    $numerador = intdiv($numerador, $divisor);
    $denominador = intdiv($denominador, $divisor);

    return $denominador === 1 ? (string) $numerador : $numerador . '/' . $denominador;
}

function gerar_pergunta_memoria(string $materia, int $ano, int $indice): array
{
    $nome = normalizar_materia_memoria($materia);
    $primeiro = 8 + ($ano * 2) + ($indice * 7);
    $segundo = 3 + (($indice * 5 + $ano) % 13);

    if (str_contains($nome, 'probabilidade')) {
        $faces = 6 + $indice;
        return ["Uma urna tem {$faces} bolas numeradas. Qual a probabilidade de retirar uma bola específica?", '1/' . $faces];
    }

    if (str_contains($nome, 'estatistica')) {
        $base = 2 + ($indice * 3);
        return ["Qual é a média de {$base}, " . ($base + 2) . ' e ' . ($base + 4) . '?', (string) ($base + 2)];
    }

    if (str_contains($nome, 'frac') || str_contains($nome, 'racional')) {
        $denominador = 4 + ($indice % 8);
        $numeradorA = 1 + ($indice % ($denominador - 1));
        $numeradorB = 1 + (($indice * 2 + 1) % ($denominador - 1));
        if ($indice % 3 === 0) {
            return ["Quanto é {$numeradorA}/{$denominador} + {$numeradorB}/{$denominador}?", simplificar_fracao_memoria($numeradorA + $numeradorB, $denominador)];
        }
        if ($indice % 3 === 1) {
            return ["Quanto é {$numeradorA}/{$denominador} × {$numeradorB}/{$denominador}?", simplificar_fracao_memoria($numeradorA * $numeradorB, $denominador * $denominador)];
        }
        $quantidade = $denominador * (4 + ($indice % 5));
        return ["Quanto é {$numeradorA}/{$denominador} de {$quantidade}?", (string) ($numeradorA * intdiv($quantidade, $denominador))];
    }

    if (str_contains($nome, 'decimal')) {
        $centavosA = 125 + ($indice * 17);
        $centavosB = 35 + ($indice * 11);
        $valorA = number_format($centavosA / 100, 2, ',', '.');
        $valorB = number_format($centavosB / 100, 2, ',', '.');
        $resposta = rtrim(rtrim(number_format(($centavosA + $centavosB) / 100, 2, ',', '.'), '0'), ',');
        return ["Quanto é {$valorA} + {$valorB}?", $resposta];
    }

    if (str_contains($nome, 'reais')) {
        $primos = [2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37];
        $irracional = $primos[$indice % count($primos)];
        $numerador = 9 + ($indice * 2);
        $denominador = 3 + ($indice % 7);
        $inteiro = 4 + $indice;
        return ["Qual destes números é irracional: √{$irracional}, {$numerador}/{$denominador}, {$inteiro} ou 0,75?", '√' . $irracional];
    }

    if (str_contains($nome, 'porcent') || str_contains($nome, 'propor') || str_contains($nome, 'razao') || str_contains($nome, 'regra de tres')) {
        if (str_contains($nome, 'porcent')) {
            $percentuais = [10, 20, 25, 50, 5, 15, 30, 40, 75, 12.5, 60, 80];
            $percentual = $percentuais[$indice % count($percentuais)];
            $base = 80 + ($indice * 40);
            $resultado = $base * $percentual / 100;
            $resposta = rtrim(rtrim(number_format($resultado, 2, ',', '.'), '0'), ',');
            return ["Quanto é {$percentual}% de {$base}?", $resposta];
        }

        $unidades = 3 + ($indice % 6);
        $precoUnitario = 4 + ($indice % 9);
        $novaQuantidade = $unidades + 5;
        return ["Se {$unidades} cadernos custam R$ " . ($unidades * $precoUnitario) . ", quanto custam {$novaQuantidade}?", 'R$ ' . ($novaQuantidade * $precoUnitario)];
    }

    if (str_contains($nome, 'mmc') || str_contains($nome, 'mdc') || str_contains($nome, 'multiplo') || str_contains($nome, 'divisor')) {
        $numeroA = 4 + ($indice % 8);
        $numeroB = 6 + (($indice * 3) % 9);
        $a = $numeroA;
        $b = $numeroB;
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }
        $mdc = max($a, 1);
        if (str_contains($nome, 'mdc') || str_contains($nome, 'divisor')) {
            return ["Qual é o maior divisor comum entre {$numeroA} e {$numeroB}?", (string) $mdc];
        }
        return ["Qual é o mínimo múltiplo comum entre {$numeroA} e {$numeroB}?", (string) intdiv($numeroA * $numeroB, $mdc)];
    }

    if (str_contains($nome, 'perimetro')) {
        $largura = 3 + ($indice % 9);
        $altura = 5 + (($indice * 2) % 8);
        return ["Qual é o perímetro de um retângulo com lados {$largura} e {$altura}?", (string) (2 * ($largura + $altura))];
    }

    if (str_contains($nome, 'area')) {
        $largura = 3 + ($indice % 9);
        $altura = 4 + (($indice * 3) % 8);
        return ["Qual é a área de um retângulo de lados {$largura} e {$altura}?", (string) ($largura * $altura)];
    }

    if (str_contains($nome, 'expressao numerica')) {
        $parcela = 3 + ($indice % 9);
        $fatorA = 2 + ($indice % 6);
        $fatorB = 3 + (($indice * 2) % 7);
        return ["Qual é o resultado de {$parcela} + {$fatorA} × {$fatorB}?", (string) ($parcela + $fatorA * $fatorB)];
    }

    if (str_contains($nome, 'volume') || str_contains($nome, 'geometria espacial')) {
        $largura = 2 + ($indice % 6);
        $altura = 3 + (($indice * 2) % 5);
        $profundidade = 4 + (($indice * 3) % 7);
        return ["Qual é o volume de um bloco de {$largura} × {$altura} × {$profundidade}?", (string) ($largura * $altura * $profundidade)];
    }

    if (str_contains($nome, 'angulo')) {
        $anguloA = 30 + ($indice % 6) * 5;
        $anguloB = 40 + ($indice % 5) * 5;
        return ["Dois ângulos de um triângulo medem {$anguloA}° e {$anguloB}°. Quanto mede o terceiro?", (string) (180 - $anguloA - $anguloB) . '°'];
    }

    if (str_contains($nome, 'grafico')) {
        $valorA = 12 + ($indice * 2);
        $valorB = $valorA + 5 + ($indice % 4);
        return ["Em um gráfico, A={$valorA} e B={$valorB}. Qual categoria tem o maior valor?", 'B'];
    }

    if (str_contains($nome, 'potenci') || str_contains($nome, 'radici') || str_contains($nome, 'notacao cientifica')) {
        $base = 2 + ($indice % 6);
        if (str_contains($nome, 'radici')) {
            $raiz = 3 + ($indice % 15);
            return ["Qual é a raiz quadrada de " . ($raiz * $raiz) . '?', (string) $raiz];
        }
        if (str_contains($nome, 'notacao cientifica')) {
            $coeficiente = 2 + ($indice % 8);
            $expoente = 3 + ($indice % 5);
            return ["Como escrever {$coeficiente} seguido de " . str_repeat('0', $expoente) . ' em notação científica?', $coeficiente . ' × 10^' . $expoente];
        }
        $expoente = 2 + ($indice % 4);
        return ["Quanto é {$base} elevado a {$expoente}?", (string) ($base ** $expoente)];
    }

    if (str_contains($nome, 'estatistica')) {
        $base = 2 + ($indice * 3);
        return ["Qual é a média de {$base}, " . ($base + 2) . ' e ' . ($base + 4) . '?', (string) ($base + 2)];
    }

    if (str_contains($nome, 'equacao') || str_contains($nome, 'funcao') || str_contains($nome, 'expressao') || str_contains($nome, 'fatoracao') || str_contains($nome, 'produto notavel') || str_contains($nome, 'sistema')) {
        if (str_contains($nome, 'sistema')) {
            $x = 2 + ($indice % 12);
            $y = 1 + (($indice * 3) % 10);
            return ["No sistema x + y = " . ($x + $y) . ' e x - y = ' . ($x - $y) . ', quanto vale x?', (string) $x];
        }
        if (str_contains($nome, 'funcao quadratica')) {
            $valorX = 2 + ($indice % 8);
            $coeficiente = 2 + ($indice % 5);
            $constante = 3 + ($indice % 7);
            return ["Se f(x) = x² + {$coeficiente}x + {$constante}, quanto vale f({$valorX})?", (string) ($valorX ** 2 + $coeficiente * $valorX + $constante)];
        }
        if (str_contains($nome, 'quadratica') || str_contains($nome, '2o grau')) {
            $raizA = 1 + ($indice % 8);
            $raizB = 3 + (($indice * 2) % 7);
            return ["Quais são as raízes de x² - " . ($raizA + $raizB) . 'x + ' . ($raizA * $raizB) . ' = 0?', $raizA . ' e ' . $raizB];
        }
        if (str_contains($nome, 'funcao')) {
            $coeficiente = 2 + ($indice % 7);
            $valorX = 2 + (($indice * 2) % 9);
            $constante = 3 + ($indice % 8);
            return ["Se f(x) = {$coeficiente}x + {$constante}, quanto vale f({$valorX})?", (string) ($coeficiente * $valorX + $constante)];
        }
        if (str_contains($nome, 'fatoracao')) {
            $raizA = 2 + ($indice % 7);
            $raizB = 4 + (($indice * 2) % 6);
            return ["Como fatorar x² - " . ($raizA + $raizB) . 'x + ' . ($raizA * $raizB) . '?', '(x - ' . $raizA . ')(x - ' . $raizB . ')'];
        }
        if (str_contains($nome, 'produto notavel')) {
            $valor = 2 + ($indice % 9);
            return ["Qual é o desenvolvimento de (x + {$valor})²?", 'x² + ' . (2 * $valor) . 'x + ' . ($valor * $valor)];
        }
        $coeficiente = 2 + ($indice % 6);
        $solucao = 3 + ($indice * 2 % 12);
        $constante = 4 + ($indice % 9);
        return ["Qual é o valor de x em {$coeficiente}x + {$constante} = " . ($coeficiente * $solucao + $constante) . '?', (string) $solucao];
    }

    if (str_contains($nome, 'pitagoras') || str_contains($nome, 'relacoes metricas')) {
        $numero = 2 + $indice;
        $proximoNumero = $numero + 1;
        $catetoA = ($proximoNumero ** 2) - ($numero ** 2);
        $catetoB = 2 * $proximoNumero * $numero;
        $hipotenusa = ($proximoNumero ** 2) + ($numero ** 2);
        return ["Um triângulo retângulo tem catetos {$catetoA} e {$catetoB}. Quanto mede a hipotenusa?", (string) $hipotenusa];
    }

    if (str_contains($nome, 'triangulo')) {
        $anguloA = 40 + ($indice % 25);
        $anguloB = 50 + (($indice * 2) % 30);
        return ["Dois ângulos de um triângulo medem {$anguloA}° e {$anguloB}°. Quanto mede o terceiro?", (string) (180 - $anguloA - $anguloB) . '°'];
    }

    if (str_contains($nome, 'quadrilatero')) {
        $anguloA = 70 + ($indice % 30);
        $anguloB = 80 + ($indice % 25);
        $anguloC = 90 + ($indice % 20);
        return ["Três ângulos de um quadrilátero medem {$anguloA}°, {$anguloB}° e {$anguloC}°. Quanto mede o quarto?", (string) (360 - $anguloA - $anguloB - $anguloC) . '°'];
    }

    if (str_contains($nome, 'geometria')) {
        $lados = 3 + $indice;
        return ["Quantos lados tem um polígono com {$lados} vértices?", (string) $lados];
    }

    if (str_contains($nome, 'semelhanca')) {
        $medida = 3 + $indice;
        $escala = 2 + ($indice % 4);
        return ["Um desenho amplia {$medida} cm na escala de {$escala}:1. Qual será a medida ampliada?", (string) ($medida * $escala) . ' cm'];
    }

    if (str_contains($nome, 'inteiro')) {
        $negativo = 5 + ($indice * 2);
        $positivo = 9 + ($indice % 11);
        switch ($indice % 3) {
            case 0:
                return ["Quanto é -{$negativo} + {$positivo}?", (string) ($positivo - $negativo)];
            case 1:
                return ["Quanto é {$positivo} - (-{$negativo})?", (string) ($positivo + $negativo)];
            default:
                return ["Quanto é -{$negativo} × {$segundo}?", (string) (-$negativo * $segundo)];
        }
    }

    if (str_contains($nome, 'divisor')) {
        $divisor = 2 + ($indice % 9);
        $multiplicador = 4 + ($indice % 8);
        return ["Qual destes divisores é o maior divisor comum de " . ($divisor * $multiplicador) . ' e ' . ($divisor * ($multiplicador + 2)) . '?', (string) $divisor];
    }

    switch ($indice % 4) {
        case 0:
            return ["Quanto é {$primeiro} + {$segundo}?", (string) ($primeiro + $segundo)];
        case 1:
            return ["Quanto é " . ($primeiro + $segundo) . " - {$segundo}?", (string) $primeiro];
        case 2:
            $fatorA = 3 + ($indice % 8);
            $fatorB = 4 + ($ano % 5) + ($indice % 6);
            return ["Quanto é {$fatorA} × {$fatorB}?", (string) ($fatorA * $fatorB)];
        default:
            $quociente = 3 + ($indice % 9);
            return ["Quanto é " . ($quociente * $segundo) . " ÷ {$segundo}?", (string) $quociente];
    }
}

function gerar_questoes_extras_memoria(string $materia, int $ano, array $paresExistentes, int $totalDesejado): array
{
    $perguntasUsadas = [];
    foreach ($paresExistentes as $par) {
        $perguntasUsadas[normalizar_materia_memoria((string) $par['pergunta'])] = true;
    }

    $extras = [];
    for ($indice = 0; count($paresExistentes) + count($extras) < $totalDesejado && $indice < $totalDesejado * 4; $indice++) {
        [$pergunta, $resposta] = gerar_pergunta_memoria($materia, $ano, $indice);
        $chavePergunta = normalizar_materia_memoria($pergunta);
        if (isset($perguntasUsadas[$chavePergunta])) {
            continue;
        }

        $perguntasUsadas[$chavePergunta] = true;
        $extras[] = [
            'id' => -($indice + 1),
            'pergunta' => $pergunta,
            'resposta' => $resposta,
        ];
    }

    return $extras;
}