<?php
declare(strict_types=1);

namespace RockSoftware\Ui\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RockSoftware\Ui\UiPlugin;
use RockSoftware\Ui\View\Helper\UiHelper;
use TestApp\View\AppView;

/**
 * The shared in-page pieces of `UiHelper`: badges, the asking post button and
 * the date and number formats.
 */
class UiHelperTest extends TestCase
{
    private UiHelper $ui;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Ui', []);
        UiPlugin::applyDefaults();
        I18n::setLocale('en_US');
        $this->ui = new UiHelper(new AppView(new ServerRequest()));
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        I18n::setLocale('en_US');
        Configure::delete('Ui');
        UiPlugin::applyDefaults();
        parent::tearDown();
    }

    public function testBadgeHasItsToneClassAndEscapesTheText(): void
    {
        $this->assertSame(
            '<span class="kk-badge kk-badge-ok">Delivered &lt;now&gt;</span>',
            $this->ui->badge('Delivered <now>', 'ok'),
        );
        $this->assertSame('<span class="kk-badge kk-badge-muted">Draft</span>', $this->ui->badge('Draft'));
    }

    public function testEveryToneRenders(): void
    {
        foreach (UiHelper::BADGE_TONES as $tone) {
            $this->assertStringContainsString('kk-badge-' . $tone . '"', $this->ui->badge('x', $tone));
        }
    }

    public function testBadgeTakesATitleAClassAndMarkup(): void
    {
        $this->assertSame(
            '<span class="kk-badge kk-badge-info ms-2" title="Since 26.10.2026"><b>3</b></span>',
            $this->ui->badge('<b>3</b>', 'info', ['title' => 'Since 26.10.2026', 'class' => 'ms-2', 'escape' => false]),
        );
    }

    public function testAnUnknownToneFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ui->badge('Delivered', 'success');
    }

    public function testPostButtonAsksThroughDataAttributesNotInlineScript(): void
    {
        $html = $this->ui->postButton('Delete', '/providers/delete/7', [
            'class' => 'btn btn-outline-danger',
            'confirm' => 'Delete "North"? Lookups stop.',
            'confirmTitle' => 'Delete this provider?',
            'confirmButton' => 'Delete',
            'confirmTone' => 'danger',
        ]);

        $this->assertStringContainsString('<form method="post" accept-charset="utf-8" class="d-inline" action="/providers/delete/7">', $html);
        $this->assertStringContainsString(
            '<button class="btn btn-outline-danger" data-confirm="Delete &quot;North&quot;? Lookups stop."'
            . ' data-confirm-title="Delete this provider?" data-confirm-button="Delete" data-confirm-tone="danger"'
            . ' type="submit">Delete</button>',
            $html,
        );
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('confirm=', str_replace('data-confirm', '', $html));
        $this->assertTrue($this->ui->isEnabled('confirm'));
    }

    public function testPostButtonWithoutAQuestionPostsAtOnce(): void
    {
        $html = $this->ui->postButton('Resend', '/messages/resend/7', [
            'confirmTitle' => 'Ignored without a question',
            'form' => ['class' => 'm-0'],
        ]);

        $this->assertStringNotContainsString('data-confirm', $html);
        $this->assertStringContainsString('class="m-0"', $html);
        $this->assertFalse($this->ui->isEnabled('confirm'));
    }

    public function testDateShowsACalendarDayAsItIs(): void
    {
        $this->assertSame('26.10.2026', $this->ui->date(new Date('2026-10-26')));
        $this->assertSame('26.10.2026', $this->ui->date('2026-10-26'));
    }

    public function testDateShowsAMomentAsItsDayInHelsinki(): void
    {
        $this->assertSame('27.10.2026', $this->ui->date(new DateTime('2026-10-26 22:30:00', 'UTC')));
        $this->assertSame('27.10.2026', $this->ui->date('2026-10-26T22:30:00Z'));
    }

    public function testDateTimeShowsHelsinkiTimeWithAPeriodBetweenHoursAndMinutes(): void
    {
        // Summer time (UTC+3) and winter time (UTC+2).
        $this->assertSame('24.10.2026 12.05', $this->ui->dateTime(new DateTime('2026-10-24 09:05:00', 'UTC')));
        $this->assertSame('26.10.2026 08.14', $this->ui->dateTime('2026-10-26T06:14:00+00:00'));
        $this->assertSame(
            '26.10.2026 08.14',
            $this->ui->dateTime(new DateTimeImmutable('2026-10-26 06:14:00', new DateTimeZone('UTC'))),
        );
        $this->assertSame('26.10.2026 08.14', $this->ui->dateTime(1792995240));
    }

    public function testTheDisplayTimezoneIsASetting(): void
    {
        Configure::write('Ui.displayTimezone', 'UTC');

        $this->assertSame('26.10.2026 06.14', $this->ui->dateTime('2026-10-26T06:14:00Z'));
    }

    public function testMissingOrUnreadableValuesShowTheEmptyText(): void
    {
        $this->assertSame('—', $this->ui->date(null));
        $this->assertSame('—', $this->ui->dateTime(''));
        $this->assertSame('', $this->ui->dateTime('not a date', ''));
        $this->assertSame('—', $this->ui->number('abc'));
        $this->assertSame('-', $this->ui->money(null, 'EUR', null, '-'));
    }

    public function testPickerValueIsWallClockTimeInHelsinki(): void
    {
        $this->assertSame('2026-10-27 08:00', $this->ui->pickerValue(new DateTime('2026-10-27 06:00:00', 'UTC')));
        $this->assertSame('2026-10-27', $this->ui->pickerValue(new DateTime('2026-10-26 22:30:00', 'UTC'), 'date'));
        $this->assertSame('08:00', $this->ui->pickerValue('2026-10-27T06:00:00Z', 'time'));
        $this->assertSame('2026-10', $this->ui->pickerValue('2026-10-27', 'month'));
        $this->assertSame('2026-10-27', $this->ui->pickerValue(new Date('2026-10-27'), 'date'));
        $this->assertSame('', $this->ui->pickerValue(null));
        $this->assertSame('', $this->ui->pickerValue('not a date'));
    }

    public function testPickerValueRefusesAnUnknownMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ui->pickerValue('2026-10-27', 'week');
    }

    public function testNumbersAndMoneyInEnglish(): void
    {
        $this->assertSame('1,842.30', $this->ui->number('1842.3', 2));
        $this->assertSame('1,843', $this->ui->number(1842.5));
        $this->assertSame('€1,842.30', $this->ui->money('1842.3'));
        $this->assertSame('$7,000', $this->ui->money(7000, 'usd', 0));
    }

    public function testNumbersAndMoneyInFinnish(): void
    {
        I18n::setLocale('fi_FI');

        $this->assertSame("1\u{a0}842,30", $this->ui->number('1842.3', 2));
        $this->assertSame("1\u{a0}842,30\u{a0}€", $this->ui->money('1842.30'));
        // CLDR writes a true minus sign in Finnish.
        $this->assertSame("\u{2212}12,50\u{a0}€", $this->ui->money(-12.5));
    }

    public function testTheLocaleSettingDecidesTheNumberFormat(): void
    {
        Configure::write('Ui.locale', 'fi');

        $this->assertSame("1\u{a0}842,30\u{a0}€", $this->ui->money('1842.30'));
    }

    public function testPercentagesOfAFractionInEnglish(): void
    {
        $this->assertSame('75%', $this->ui->percent(0.75));
        $this->assertSame('4.3%', $this->ui->percent('0.0425', 1));
        $this->assertSame('1,250%', $this->ui->percent(12.5));
        $this->assertSame('-12.5%', $this->ui->percent(-0.125, 1));
        $this->assertSame('0%', $this->ui->percent(0));
    }

    public function testPercentagesOfAFractionInFinnish(): void
    {
        I18n::setLocale('fi_FI');

        $this->assertSame("75\u{a0}%", $this->ui->percent(0.75));
        $this->assertSame("4,3\u{a0}%", $this->ui->percent('0.0425', 1));
        $this->assertSame("\u{2212}12,5\u{a0}%", $this->ui->percent(-0.125, 1));
    }

    public function testATinyNegativeThatRoundsToZeroHasNoMinusInEnglish(): void
    {
        $this->assertSame('0.00', $this->ui->number(-0.004, 2));
        $this->assertSame('0', $this->ui->number(-0.4));
        $this->assertSame('0', $this->ui->number(-0.0));
        $this->assertSame('-0.01', $this->ui->number('-0.006', 2));
        $this->assertSame('€0.00', $this->ui->money('-0.001'));
        $this->assertSame('€0', $this->ui->money(-0.4, 'EUR', 0));
        $this->assertSame('¥0', $this->ui->money(-0.4, 'JPY'));
        $this->assertSame('0.0%', $this->ui->percent(-0.0004, 1));
        $this->assertSame('0%', $this->ui->percent(-0.004));
        $this->assertSame('-1%', $this->ui->percent(-0.006));
    }

    public function testATinyNegativeThatRoundsToZeroHasNoMinusInFinnish(): void
    {
        I18n::setLocale('fi_FI');

        $this->assertSame('0,00', $this->ui->number(-0.004, 2));
        $this->assertSame('0', $this->ui->number(-0.4));
        $this->assertSame("0,00\u{a0}€", $this->ui->money('-0.001'));
        $this->assertSame("0,0\u{a0}%", $this->ui->percent(-0.0004, 1));
        $this->assertSame("\u{2212}0,01", $this->ui->number(-0.006, 2));
    }

    public function testAMissingPercentageShowsTheEmptyText(): void
    {
        $this->assertSame('—', $this->ui->percent(null));
        $this->assertSame('—', $this->ui->percent('abc'));
        $this->assertSame('', $this->ui->percent('', 0, ''));
    }

    public function testTheNumberLocaleSettingKeepsTheTextsLanguage(): void
    {
        Configure::write('Ui.numberLocale', 'fi_FI');

        $this->assertSame("1\u{a0}842,30\u{a0}€", $this->ui->money('1842.30'));
        $this->assertSame("75\u{a0}%", $this->ui->percent(0.75));
        $this->ui->requireComponent('format');
        $this->assertSame('fi-FI', $this->ui->componentTexts()['numberLocale']);
        $this->assertSame('en', substr((string)$this->ui->componentTexts()['locale'], 0, 2));
    }

    public function testTheListTableReadsTheStorageKeyPrefix(): void
    {
        Configure::write('Ui.storageKeyPrefix', 'demo');
        $this->ui->requireComponent('listTable');

        $this->assertSame('demo', $this->ui->componentTexts()['storageKeyPrefix']);
    }

    public function testErrorTextByStatus(): void
    {
        $this->assertSame('Page not found', $this->ui->errorText(404)['title']);
        $this->assertSame('You do not have access to this page', $this->ui->errorText(403)['title']);
        $this->assertSame('The request could not be handled', $this->ui->errorText(400)['title']);
        $this->assertSame('The request could not be handled', $this->ui->errorText(405)['title']);
        $this->assertSame('Something went wrong', $this->ui->errorText(500)['title']);
        $this->assertSame('Something went wrong', $this->ui->errorText(503)['title']);
        $this->assertSame('The page could not be shown. Try again in a moment.', $this->ui->errorText(500)['text']);
    }

    public function testErrorTextInFinnish(): void
    {
        I18n::setLocale('fi_FI');

        $this->assertSame(['title' => 'Sivua ei löydy', 'text' => 'Sivua ei ole tai se on siirretty. Tarkista osoite.'], $this->ui->errorText(404));
        $this->assertSame('Sinulla ei ole oikeutta tähän sivuun', $this->ui->errorText(403)['title']);
        $this->assertSame('Jotain meni pieleen', $this->ui->errorText(500)['title']);
    }

    public function testThePosixLocaleGroupsThousandsAsEnglish(): void
    {
        I18n::setLocale('en_US_POSIX');

        $this->assertSame('1,842', $this->ui->number(1842));
    }
}
