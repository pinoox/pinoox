<?php

namespace Pinoox\Component\Log;

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Auth;

class ContextProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;
        $extra['package'] = App::package();

        if (class_exists(Auth::class)) {
            try {
                $userId = Auth::id();
                if ($userId !== null) {
                    $extra['user_id'] = $userId;
                }
            } catch (\Throwable) {
            }
        }

        return $record->with(extra: $extra);
    }
}

