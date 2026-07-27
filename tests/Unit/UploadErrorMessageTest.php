<?php

namespace Tests\Unit;

use Tests\TestCase;

class UploadErrorMessageTest extends TestCase
{
    public function test_the_upload_error_does_not_expose_livewire_internal_state(): void
    {
        $message = trans('validation.uploaded', [
            'attribute' => 'mountedActions.0.data.files.upload-id',
        ], 'pt_BR');

        $this->assertSame(
            'Não foi possível enviar o arquivo. Ele pode ser maior do que o limite aceito pelo servidor. Verifique o tamanho e tente novamente.',
            $message,
        );
        $this->assertStringNotContainsString('mountedActions', $message);
    }
}
