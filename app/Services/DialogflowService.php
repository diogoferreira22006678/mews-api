<?php

namespace App\Services;

use Google\Cloud\Dialogflow\V2\Client\SessionsClient;
use Google\Cloud\Dialogflow\V2\DetectIntentRequest;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\TextInput;

class DialogflowService
{
    private SessionsClient $client;
    private string $projectId;
    private string $language;

    public function __construct()
    {
        $this->projectId = config('services.dialogflow.project_id');
        $this->language  = config('services.dialogflow.language', 'en');

        $credentialsPath = config('services.dialogflow.credentials');

        $this->client = new SessionsClient([
            'credentials' => $credentialsPath,
        ]);
    }

    public function detectIntent(string $text, ?string $sessionId = null): array
    {
        $sessionId = $sessionId ?: 'session_' . bin2hex(random_bytes(8));
        $session = $this->client->sessionName($this->projectId, $sessionId);

        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($this->language);

        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        $request = new DetectIntentRequest([
            'session' => $session,
            'query_input' => $queryInput,
        ]);

        $response = $this->client->detectIntent($request);
        $queryResult = $response->getQueryResult();

        $intentName = $queryResult->getIntent() ? $queryResult->getIntent()->getDisplayName() : null;

        // parameters é Struct -> converter para array simples
        $paramsStruct = $queryResult->getParameters();
        $params = $paramsStruct ? $paramsStruct->getFields() : [];

        $parameters = [];
        foreach ($params as $key => $value) {
            // value é Value (protobuf)
            $parameters[$key] = $this->protobufValueToPhp($value);
        }

        return [
            'intent' => $intentName,
            'parameters' => $parameters,
            'session_id' => $sessionId,
        ];
    }

    private function protobufValueToPhp($value)
    {
        // Basic conversion covering common types
        if ($value->hasStringValue()) return $value->getStringValue();
        if ($value->hasNumberValue()) return $value->getNumberValue();
        if ($value->hasBoolValue()) return $value->getBoolValue();
        if ($value->hasStructValue()) {
            $out = [];
            foreach ($value->getStructValue()->getFields() as $k => $v) {
                $out[$k] = $this->protobufValueToPhp($v);
            }
            return $out;
        }
        if ($value->hasListValue()) {
            $out = [];
            foreach ($value->getListValue()->getValues() as $v) {
                $out[] = $this->protobufValueToPhp($v);
            }
            return $out;
        }
        return null;
    }
}