<?php

namespace Hadary\IgnitionSolutionProvider;

use Exception;
use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\HasSolutionsForThrowable;
use Throwable;
use function curl_close;
use function curl_exec;
use function curl_init;
use function json_encode;

class SolutionProvider implements HasSolutionsForThrowable
{
    public function canSolve(Throwable $throwable): bool
    {
        try {
            $response = $this->makeRequest('solvable', $this->createErrorReport($throwable));

            if (empty($response['solvable'])) {
                return false;
            }

            return $response['solvable'];
        } catch (Exception $exception) {
            return false;
        }
    }

    public function getSolutions(Throwable $throwable): array
    {
        try {
            $response = $this->makeRequest('solution', $this->createErrorReport($throwable));

            if (empty($response['title'])) {
                return [];
            }

            if (empty($response['description'])) {
                return [];
            }

            $solution = new BaseSolution($response['title']);
            $solution->setSolutionDescription($response['description']);

            if (!empty($response['links'])) {
                $solution->setDocumentationLinks($response['links']);
            }

            return [$solution];
        } catch (Exception $exception) {
            return [];
        }
    }

    private function makeRequest(string $url, array $errorReport): array
    {
        $curl = curl_init('https://hadary.ai/api/ignition/'.$url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($errorReport));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 500);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 1000);

        $response = curl_exec($curl);

        curl_close($curl);

        if (empty($response)) {
            return [];
        }

        $body = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $body;
    }

    private function createErrorReport(Throwable $throwable): array
    {
        $errorReport = [
            'type' => get_class($throwable),
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'stacktrace' => $throwable->getTrace(),
            'file_contents' => file_get_contents($throwable->getFile())
        ];

        $previousThrowable = $throwable->getPrevious();

        if ($previousThrowable !== null) {
            $errorReport['previous'] = [
                'type' => get_class($throwable),
                'message' => $previousThrowable->getMessage(),
                'code' => $previousThrowable->getCode(),
                'file' => $previousThrowable->getFile(),
                'line' => $previousThrowable->getLine(),
                'stacktrace' => $previousThrowable->getTrace(),
                'file_contents' => file_get_contents($previousThrowable->getFile())
            ];
        }

        return $errorReport;
    }
}
