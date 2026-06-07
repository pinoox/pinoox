<?php

use Pinoox\Component\File\FileConfig;
use Pinoox\Component\File\UploadBuilder;
use Pinoox\Component\File\UploadResult;
use Pinoox\Portal\File;

it('declares the File portal contract', function () {
    expectPortalContract(File::class);
});

it('exposes upload builder and result types', function () {
    expect(class_exists(File::class))->toBeTrue()
        ->and(class_exists(UploadBuilder::class))->toBeTrue()
        ->and(class_exists(UploadResult::class))->toBeTrue()
        ->and(class_exists(FileConfig::class))->toBeTrue();
});

it('resolves default filesystem config keys', function () {
    $config = FileConfig::resolve();

    expect($config)->toHaveKeys(['package', 'disk', 'default_access', 'thumb_width', 'thumb_height'])
        ->and($config['default_access'])->toBe('public')
        ->and($config['thumb_width'])->toBe(512)
        ->and($config['thumb_height'])->toBe(512);
});

it('builds upload result objects', function () {
    $fail = UploadResult::fail('invalid_extension');
    $disk = UploadResult::disk('/tmp/sample.zip');

    expect($fail->success)->toBeFalse()
        ->and($fail->error)->toBe('invalid_extension')
        ->and($disk->success)->toBeTrue()
        ->and($disk->path)->toBe('/tmp/sample.zip');
});

it('parses max size units on upload builder', function () {
    $builder = File::upload('avatar')
        ->to('uploads/avatar')
        ->maxSize('2MB')
        ->extensions('jpg,png');

    expect($builder)->toBeInstanceOf(UploadBuilder::class);
});

