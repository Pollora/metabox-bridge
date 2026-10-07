<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Pollora\Metabox\Enums\ModelSupport;
use Pollora\MetaboxBridge\Eloquent\Casts\SerializedArray;
use Pollora\MetaboxBridge\Eloquent\Concerns\HasCustomTable;
use Pollora\MetaboxBridge\Eloquent\CustomTableRow;
use Pollora\MetaboxBridge\Eloquent\ModelRecord;

class EventDetails extends CustomTableRow
{
    protected $table = 'events';

    protected $fillable = ['ID', 'venue', 'speakers'];

    protected $casts = ['speakers' => SerializedArray::class];
}

class Transaction extends ModelRecord
{
    public $timestamps = true;

    protected $table = 'transactions';

    protected $fillable = ['amount'];
}

class TestPost extends Model
{
    use HasCustomTable;

    public $timestamps = false;

    protected $table = 'posts';

    protected $primaryKey = 'ID';

    protected $fillable = ['post_title'];

    public function details()
    {
        return $this->hasCustomTable(EventDetails::class);
    }
}

beforeEach(function () {
    $GLOBALS['wp_cache_deleted'] = [];

    Schema::create('posts', function (Blueprint $table) {
        $table->id('ID');
        $table->string('post_title');
    });
    Schema::create('events', function (Blueprint $table) {
        $table->metaboxObject();
        $table->string('venue')->nullable();
        $table->text('speakers')->nullable();
    });
    Schema::create('transactions', function (Blueprint $table) {
        $table->metaboxModel(ModelSupport::PublishedDate, ModelSupport::ModifiedDate);
        $table->decimal('amount', 10, 2)->nullable();
    });
});

test('a custom table row uses the object ID', function () {
    EventDetails::create(['ID' => 42, 'venue' => 'Lyon']);

    expect(EventDetails::find(42)->venue)->toBe('Lyon')
        ->and(EventDetails::find(42)->getKey())->toBe(42);
});

test('arrays are stored serialized, like Meta Box', function () {
    EventDetails::create(['ID' => 1, 'speakers' => ['Ada', 'Grace']]);

    expect(EventDetails::query()->toBase()->value('speakers'))->toBe(serialize(['Ada', 'Grace']))
        ->and(EventDetails::find(1)->speakers)->toBe(['Ada', 'Grace']);
});

test('values written by Meta Box are read, objects never unserialized', function () {
    EventDetails::query()->insert([
        ['ID' => 1, 'speakers' => serialize(['Ada'])],
        ['ID' => 2, 'speakers' => 'a plain string'],
        ['ID' => 3, 'speakers' => serialize([new ArrayObject])],
    ]);

    expect(EventDetails::find(1)->speakers)->toBe(['Ada'])
        ->and(EventDetails::find(2)->speakers)->toBe('a plain string')
        ->and(EventDetails::find(3)->speakers[0])->toBeInstanceOf(__PHP_Incomplete_Class::class);
});

test('saving and deleting a row clears the Meta Box cache', function () {
    $row = EventDetails::create(['ID' => 7, 'venue' => 'Lyon']);
    $row->update(['venue' => 'Paris']);
    $row->delete();

    expect($GLOBALS['wp_cache_deleted'])->toBe(array_fill(0, 3, [7, 'rwmb_wp_events_table_data']));
});

test('a model record has an auto-incremented ID and the Meta Box dates', function () {
    $first = Transaction::create(['amount' => 10]);
    $second = Transaction::create(['amount' => 20]);

    expect($second->getKey())->toBe($first->getKey() + 1)
        ->and($second->published_date)->not->toBeNull()
        ->and($second->modified_date)->not->toBeNull()
        ->and($GLOBALS['wp_cache_deleted'])->toContain([$second->getKey(), 'rwmb_wp_transactions_table_data']);
});

test('HasCustomTable links an object to its row', function () {
    $post = TestPost::query()->create(['post_title' => 'Conference']);
    EventDetails::create(['ID' => $post->getKey(), 'venue' => 'Lyon']);

    expect($post->details->venue)->toBe('Lyon')
        ->and(TestPost::with('details')->first()->details->venue)->toBe('Lyon');
});
