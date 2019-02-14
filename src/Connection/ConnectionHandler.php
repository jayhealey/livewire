<?php

namespace Livewire\Connection;

use Illuminate\Validation\ValidationException;
use Livewire\LivewireComponentWrapper;

abstract class ConnectionHandler
{
    public function handle($event, $data, $serialized)
    {
        $instance = decrypt($serialized);
        $wrapped = LivewireComponentWrapper::wrap($instance);

        try {
            $this->processEvent($event, $wrapped, $data);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
        }

        if ($instance->redirectTo) {
            return ['redirectTo' => $instance->redirectTo];
        }

        $id = $instance->id;
        $dom = $wrapped->output($errors ?? null);
        $dirtyInputs = $wrapped->dirtyInputs();
        $callOnParent = $instance->callOnParent;
        $serialized = encrypt($instance);

        return [
            'id' => $id,
            'dom' => app('livewire')->injectDataForJsInComponentRootAttributes($dom, $id, $serialized),
            'dirtyInputs' => $dirtyInputs,
            'serialized' => $serialized,
            'ref' => $data['ref'] ?? null,
            'callOnParent' => $callOnParent,
        ];
    }

    public function processEvent($event, $wrapped, $data)
    {
        $wrapped->beforeUpdate();

        switch ($event) {
            case 'refresh':
                break;
            case 'syncInput':
                $wrapped->syncInput($data['name'], $data['value']);
                break;
            case 'fireMethod':
                $wrapped->fireMethod($data['method'], $data['params']);
                break;
            default:
                throw new \Exception('Unrecongnized event: ' . $event);
                break;
        }

        $wrapped->updated();
    }
}
