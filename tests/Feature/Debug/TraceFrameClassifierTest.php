<?php

namespace Feature;

use PHPUnit\Framework\TestCase;
use Pinoox\Component\Kernel\Debug\Support\TraceFrameClassifier;

class TraceFrameClassifierTest extends TestCase
{
    public function test_finds_first_user_frame_skipping_pincore_and_vendor(): void
    {
        $traces = [
            ['file' => 'C:/pinoox/pincore/Component/Source/Portal.php', 'line' => 227, 'function' => 'callMethod'],
            ['file' => 'C:/pinoox/pincore/Component/Source/Portal.php', 'line' => 306, 'function' => '__callStatic'],
            ['file' => 'C:/pinoox/apps/com_pinoox_installer/routes/actions.php', 'line' => 16, 'function' => '__callStatic'],
            ['file' => 'C:/pinoox/pincore/Component/Kernel/Kernel.php', 'line' => 100, 'function' => 'handle'],
        ];

        $origin = TraceFrameClassifier::findOriginIndex($traces);

        $this->assertSame(2, $origin);
        $this->assertTrue(TraceFrameClassifier::isPincore($traces[0]));
        $this->assertTrue(TraceFrameClassifier::isPortalCore($traces[0]));
        $this->assertStringContainsString('trace-from-pincore', TraceFrameClassifier::lineClasses($traces[0], $origin, 0));
        $this->assertStringContainsString('trace-from-portal-core', TraceFrameClassifier::lineClasses($traces[0], $origin, 0));
        $this->assertStringContainsString('trace-portal-origin', TraceFrameClassifier::lineClasses($traces[2], $origin, 2));
    }

    public function test_skips_project_index_php_when_finding_origin(): void
    {
        $traces = [
            ['file' => 'C:/pinoox/index.php', 'line' => 11, 'function' => 'require'],
            ['file' => 'C:/pinoox/pincore/Component/Kernel/Kernel.php', 'line' => 100, 'function' => 'handle'],
            ['file' => 'C:/pinoox/apps/com_acme_shop/routes/web.php', 'line' => 8, 'function' => '{closure}'],
        ];

        $origin = TraceFrameClassifier::findOriginIndex($traces, 'C:/pinoox');

        $this->assertSame(2, $origin);
        $this->assertTrue(TraceFrameClassifier::isProjectEntry($traces[0], 'C:/pinoox'));
        $this->assertStringContainsString('trace-from-pincore', TraceFrameClassifier::lineClasses($traces[0], $origin, 0, 'C:/pinoox'));
    }

    public function test_skips_system_layer_when_finding_origin(): void
    {
        $traces = [
            ['file' => 'C:/pinoox/launcher/bootstrap.php', 'line' => 20, 'function' => 'boot'],
            ['file' => 'C:/pinoox/apps/com_acme_shop/routes/web.php', 'line' => 8, 'function' => '{closure}'],
        ];

        $origin = TraceFrameClassifier::findOriginIndex($traces, 'C:/pinoox');

        $this->assertSame(1, $origin);
        $this->assertTrue(TraceFrameClassifier::isSystem($traces[0], 'C:/pinoox'));
        $this->assertStringContainsString('trace-from-pincore', TraceFrameClassifier::lineClasses($traces[0], $origin, 0, 'C:/pinoox'));
    }
}

