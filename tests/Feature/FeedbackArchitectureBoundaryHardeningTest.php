<?php

use LBHurtado\XFeedback\Contracts\FeedbackJournalEventMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackUiComponentPresenterContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRecordData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackJournalEventData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackUiComponentData;

it('does not depend on host workflow audit action campaign or cockpit packages', function () {
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true);
    $requires = array_keys([
        ...(array) ($composer['require'] ?? []),
        ...(array) ($composer['require-dev'] ?? []),
    ]);

    expect($requires)->not->toContain('lbhurtado/x-change')
        ->and($requires)->not->toContain('3neti/x-change')
        ->and($requires)->not->toContain('lbhurtado/x-journal')
        ->and($requires)->not->toContain('3neti/x-journal')
        ->and($requires)->not->toContain('lbhurtado/x-action')
        ->and($requires)->not->toContain('3neti/x-action')
        ->and($requires)->not->toContain('lbhurtado/x-campaign')
        ->and($requires)->not->toContain('3neti/x-campaign')
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XCampaign\\XCampaignServiceProvider'))->toBeFalse();
});

it('does not define cockpit pages controllers routes jobs or workflow mutation surfaces', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http/Controllers'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(is_dir($packageRoot.'/resources/views'))->toBeFalse()
        ->and(is_dir($packageRoot.'/resources/js'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Actions'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Workflows'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Cockpit'))->toBeFalse();
});

it('keeps delivery records as communication state rather than lifecycle or audit truth', function () {
    $migration = (string) file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_07_01_000001_create_feedback_delivery_records_table.php');

    expect($migration)->toContain('feedback_delivery_records')
        ->and($migration)->not->toContain('workflow_status')
        ->and($migration)->not->toContain('lifecycle_status')
        ->and($migration)->not->toContain('settlement_status')
        ->and($migration)->not->toContain('audit_log')
        ->and($migration)->not->toContain('journal_entries');
});

it('keeps journal integration as portable handoff data only', function () {
    $event = app(FeedbackJournalEventMapperContract::class)->fromRecord(feedbackArchitectureRecord());

    expect($event)->toBeInstanceOf(FeedbackJournalEventData::class)
        ->and($event->meta['journal_handoff_only'])->toBeTrue()
        ->and($event->meta)->not->toHaveKey('journal_entry_id')
        ->and(is_dir(dirname(__DIR__, 2).'/database/migrations'))->toBeTrue()
        ->and(feedbackArchitectureContains('JournalEntry'))->toBeFalse();
});

it('keeps ui components portable and page-free', function () {
    $component = app(FeedbackUiComponentPresenterContract::class)->deliveryStatusBadge(new FeedbackDeliveryConsoleRecordData(
        delivery_id: 'delivery-1',
        intent_key: 'claim.succeeded.claimant',
        channel: 'sms',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1'),
        status: FeedbackDeliveryData::StatusSent,
    ));

    expect($component)->toBeInstanceOf(FeedbackUiComponentData::class)
        ->and($component->component)->toBe('DeliveryStatusBadge')
        ->and($component->meta['portable'])->toBeTrue()
        ->and($component->meta['cockpit_page'])->toBeFalse();
});

function feedbackArchitectureRecord(): LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData
{
    return new LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData(
        intent_key: 'claim.succeeded.claimant',
        channel: 'sms',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1'),
        status: FeedbackDeliveryData::StatusSent,
        provider_message_id: 'sms-1',
    );
}

function feedbackArchitectureContains(string $needle): bool
{
    $root = dirname(__DIR__, 2).'/src';
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        if (str_contains((string) file_get_contents($file->getPathname()), $needle)) {
            return true;
        }
    }

    return false;
}
