<?php

namespace App\Console\Commands;

use App\Services\RevalidateContent;
use Illuminate\Console\Command;

class RevalidateCms extends Command
{
    protected $signature = 'clinic:revalidate-content';

    protected $description = 'Retry the Next.js content cache invalidation webhook';

    public function handle(): int
    {
        if (app(RevalidateContent::class)->send()) {
            $this->info('Contenido invalidado en Next.js.');

            return self::SUCCESS;
        }
        $this->error('Next.js no confirmó la invalidación. Verifique URL, secreto y disponibilidad. La caché conserva su expiración de respaldo.');

        return self::FAILURE;
    }
}
