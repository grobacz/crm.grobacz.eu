<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class GraphQLController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'args' => [
                        'name' => ['type' => Type::string()]
                    ],
                    'resolve' => function ($root, $args) {
                        return "Hello " . ($args['name'] ?? 'World');
                    }
                ],
                'customers' => [
                    'type' => Type::listOf(Type::string()),
                    'resolve' => function () {
                        return [];
                    }
                ],
            ]
        ]);

        $schema = new Schema([
            'query' => $queryType,
        ]);

        $rawBody = $request->getContent();
        $input = json_decode($rawBody, true);

        $query = $input['query'] ?? '';
        $variables = $input['variables'] ?? null;

        $result = GraphQL::executeQuery($schema, $query, null, null, $variables);

        return $this->json($result->toArray());
    }
}
