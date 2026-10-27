<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$usuarios = [
    ['id'=>1,'nome'=>'João Silva','email'=>'joao@email.com','login'=>'joao','senha'=>'1234'],
    ['id'=>2,'nome'=>'Maria Souza','email'=>'maria@email.com','login'=>'maria','senha'=>'1234'],
    ['id'=>3,'nome'=>'Pedro Santos','email'=>'pedro@email.com','login'=>'pedro','senha'=>'1234'],
    ['id'=>4,'nome'=>'Ana Oliveira','email'=>'ana@email.com','login'=>'ana','senha'=>'1234'],
    ['id'=>5,'nome'=>'Carlos Lima','email'=>'carlos@email.com','login'=>'carlos','senha'=>'1234']
];

function resposta(Response $response, $dados, int $status = 200): Response
{
    $response->getBody()->write(json_encode($dados));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

/* STATUS */
$app->get('/status', function (Request $request, Response $response) {
    return resposta($response, ['status'=>'API funcionando']);
});

/* LISTAR USUÁRIOS */
$app->get('/usuarios', function (Request $request, Response $response) use (&$usuarios) {
    $lista = $usuarios;

    foreach ($lista as &$usuario) {
        unset($usuario['senha']);
    }

    return resposta($response, $lista);
});

/* BUSCAR USUÁRIO */
$app->get('/usuarios/{id}', function (Request $request, Response $response, array $args) use (&$usuarios) {
    foreach ($usuarios as $usuario) {
        if ($usuario['id'] == $args['id']) {
            unset($usuario['senha']);
            return resposta($response, $usuario);
        }
    }

    return resposta($response, ['erro'=>'Usuário não encontrado'], 404);
});

/* CADASTRAR */
$app->post('/usuarios', function (Request $request, Response $response) use (&$usuarios) {
    $dados = $request->getParsedBody();

    $usuario = [
        'id' => count($usuarios) + 1,
        'nome' => $dados['nome'],
        'email' => $dados['email'],
        'login' => $dados['login'],
        'senha' => $dados['senha']
    ];

    $usuarios[] = $usuario;
    unset($usuario['senha']);

    return resposta($response, $usuario, 201);
});

/* ATUALIZAR */
$app->put('/usuarios/{id}', function (Request $request, Response $response, array $args) use (&$usuarios) {
    $dados = $request->getParsedBody();

    foreach ($usuarios as &$usuario) {
        if ($usuario['id'] == $args['id']) {
            $usuario['nome'] = $dados['nome'] ?? $usuario['nome'];
            $usuario['email'] = $dados['email'] ?? $usuario['email'];
            $usuario['login'] = $dados['login'] ?? $usuario['login'];

            $retorno = $usuario;
            unset($retorno['senha']);

            return resposta($response, $retorno);
        }
    }

    return resposta($response, ['erro'=>'Usuário não encontrado'], 404);
});

/* EXCLUIR */
$app->delete('/usuarios/{id}', function (Request $request, Response $response, array $args) use (&$usuarios) {
    foreach ($usuarios as $i => $usuario) {
        if ($usuario['id'] == $args['id']) {
            array_splice($usuarios, $i, 1);
            return resposta($response, ['mensagem'=>'Usuário removido']);
        }
    }

    return resposta($response, ['erro'=>'Usuário não encontrado'], 404);
});

/* LOGIN */
$app->post('/login', function (Request $request, Response $response) use (&$usuarios) {
    $dados = $request->getParsedBody();

    foreach ($usuarios as $usuario) {
        if ($usuario['login'] == $dados['login'] && $usuario['senha'] == $dados['senha']) {
            unset($usuario['senha']);
            return resposta($response, [
                'mensagem'=>'Login realizado com sucesso',
                'usuario'=>$usuario
            ]);
        }
    }

    return resposta($response, ['erro'=>'Login ou senha inválidos'], 401);
});

/* ALTERAR SENHA */
$app->put('/usuarios/{id}/senha', function (Request $request, Response $response, array $args) use (&$usuarios) {
    $dados = $request->getParsedBody();

    foreach ($usuarios as &$usuario) {
        if ($usuario['id'] == $args['id']) {
            if ($usuario['senha'] != $dados['senha_atual']) {
                return resposta($response, ['erro'=>'Senha atual incorreta'], 401);
            }

            $usuario['senha'] = $dados['nova_senha'];

            return resposta($response, ['mensagem'=>'Senha alterada com sucesso']);
        }
    }

    return resposta($response, ['erro'=>'Usuário não encontrado'], 404);
});

$app->run();