<?php

use PHPUnit\Framework\MockObject\MockObject;
use pinoox\component\store\config\Config;
use pinoox\component\store\config\strategy\ConfigStrategyInterface;

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
