<?php
require_once __DIR__ . '/../Commodities/BotError.php';

require_once __DIR__ . '/../Sources/Log/LogRecord.php';
require_once __DIR__ . '/../Sources/Log/LogFormatterInterface.php';
require_once __DIR__ . '/../Sources/Log/PlainTextLogFormatter.php';
require_once __DIR__ . '/../Sources/Log/JsonLogFormatter.php';
require_once __DIR__ . '/../Sources/Log/LogHandlerInterface.php';
require_once __DIR__ . '/../Sources/Log/ConsoleLogHandler.php';
require_once __DIR__ . '/../Sources/Log/FileLogHandler.php';
require_once __DIR__ . '/../Sources/Log/DbLogHandler.php';
require_once __DIR__ . '/../Sources/Log/LogDispatcher.php';

require_once __DIR__ . '/Stubs/FakeDb.php';
require_once __DIR__ . '/Stubs/FakeSettings.php';
require_once __DIR__ . '/Stubs/RecursingFakeSettings.php';
require_once __DIR__ . '/Stubs/StubBot.php';

require_once __DIR__ . '/../Commodities/00_BasePassiveModule.php';
require_once __DIR__ . '/../Commodities/01_BaseActiveModule.php';
require_once __DIR__ . '/Stubs/FakeSettingsCore.php';
require_once __DIR__ . '/Stubs/FakeTimerCore.php';
require_once __DIR__ . '/Stubs/FakeNoOpCore.php';

require_once __DIR__ . '/Stubs/FakeRelayDb.php';
require_once __DIR__ . '/Stubs/FakeChatCore.php';
require_once __DIR__ . '/Stubs/FakeDispatcher.php';
require_once __DIR__ . '/Stubs/FakeSecurityCore.php';
require_once __DIR__ . '/Stubs/FakePlayerCore.php';
require_once __DIR__ . '/Stubs/FakeRelayBot.php';
// Modules/Relay.php self-instantiates against whatever $bot is in scope the moment it's
// required (the framework's normal module auto-load convention) - give it a throwaway fake here
// so that one-time side effect doesn't fatal error. Tests construct their own fresh Relay($bot)
// instances against their own FakeRelayBot to exercise the constructor for real.
$bot = new FakeRelayBot();
require_once __DIR__ . '/../Modules/Relay.php';
