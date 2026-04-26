<?php

namespace App\Controller;

use App\GraphQL\Exception\ValidationException;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use App\GraphQL\Types\MutationType;
use App\GraphQL\Types\QueryType;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GraphQLController extends AbstractController
{
    public function __construct(
        private readonly QueryType $queryType,
        private readonly MutationType $mutationType,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $schema = new Schema([
            'query' => $this->queryType,
            'mutation' => $this->mutationType,
        ]);

        $rawBody = $request->getContent();
        $input = json_decode($rawBody, true);

        $query = $input['query'] ?? '';
        $variables = $input['variables'] ?? null;
        $operationName = $input['operationName'] ?? null;

        $result = GraphQL::executeQuery(
            $schema,
            $query,
            null,
            null,
            $variables,
            $operationName
        );

        $result->setErrorFormatter(static function (Error $error): array {
            $formatted = FormattedError::createFromException($error, DebugFlag::INCLUDE_DEBUG_MESSAGE);
            $previous = $error->getPrevious();

            if ($previous instanceof ValidationException) {
                $formatted['extensions'] = array_merge(
                    $formatted['extensions'] ?? [],
                    [
                        'code' => $previous->getErrorCode(),
                        'fieldErrors' => $previous->getFieldErrors(),
                    ]
                );
            }

            return $formatted;
        });

        return $this->json($result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE));
    }
}
