<?php

declare(strict_types=1);

test('health endpoint returns successful status', function () {
    $response = $this->get('/health');

    $response->assertOk();
});

test('welcome page renders successfully with Fincab product landing content', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Fincab');
    $response->assertSee('Control presupuestario quincenal');
    $response->assertSee('Pay Debt First');
    $response->assertSee('Termómetro de Deudas');
    $response->assertSee('Comenzar Ahora con Magic Link');
});
