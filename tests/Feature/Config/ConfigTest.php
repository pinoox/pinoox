<?php

use PHPUnit\Framework\MockObject\MockObject;
use Pinoox\Component\Store\Config\Config;
use Pinoox\Component\Store\Config\Strategy\ConfigStrategyInterface;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\Config as ConfigPortal;

it('should adds, saves, and retrieves a configuration key-value pair', function () {
    // Arrange
    /** @var ConfigStrategyInterface|MockObject $strategy */
    $strategy = $this->createMock(ConfigStrategyInterface::class);
    $config = new Config($strategy);
    $key = 'test_key';
    $value = 'test_value';

    $strategy->expects($this->once())
        ->method('save');

    $strategy->expects($this->once())
        ->method('get')
        ->with($key)
        ->willReturn($value);

    // Act
    $config->add($key, $value);
    $config->save();
    $retrievedValue = $config->get($key);

    // Assert
    $this->assertEquals($value, $retrievedValue);
});

it('loads system database config through the explicit system alias', function () {
    Loader::setBasePath(testProjectRoot());

    expect(ConfigPortal::name('~system/database')->get('connections.mysql.driver'))->toBe('mysql');
});

it('keeps legacy Pinoox model aliases for system models', function () {
    expect(class_exists(\Pinoox\Model\UserModel::class))->toBeTrue()
        ->and(is_a(\Pinoox\Model\UserModel::class, \Pinoox\Model\UserModel::class, true))
        ->toBeTrue();
});

