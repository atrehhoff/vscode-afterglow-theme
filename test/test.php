<?php
/**
 * Afterglow Remastered — PHP syntax showcase
 *
 * @package Afterglow\Demo
 */

declare(strict_types=1);

namespace Afterglow\Demo;

use DateTimeImmutable;
use JsonSerializable;
use RuntimeException;
use function array_map;
use const PHP_EOL;

// ---------------------------------------------------------------
// Constants, enums, interfaces
// ---------------------------------------------------------------

const MAX_ITEMS = 42;
define('APP_NAME', 'Afterglow');

enum Status: string {
    case Active   = 'active';
    case Archived = 'archived';
    case Pending  = 'pending';

    public function label(): string {
        return match ($this) {
            Status::Active   => 'Active ✔',
            Status::Archived => 'Archived',
            Status::Pending  => 'Pending',
        };
    }
}

interface Renderable {
    public function render(): string;
}

trait Timestamped {
    public ?DateTimeImmutable $updatedAt = null;

    public function touch(): void {
        $this->updatedAt = new DateTimeImmutable();
    }
}

abstract class Entity implements JsonSerializable {
    public readonly DateTimeImmutable $createdAt;

    public function __construct(
        protected readonly int $id,
        protected string $name,
    ) {}

    abstract public function type(): string;

    public function jsonSerialize(): array {
        return ['id' => $this->id, 'name' => $this->name, 'type' => $this->type()];
    }
}

final class Article extends Entity implements Renderable {
    use Timestamped;

    /** @var list<string> */
    private array $tags = [];

    public function __construct(
        int $id,
        string $name,
        private Status $status = Status::Pending,
    ) {
        parent::__construct($id, $name);
        $this->createdAt = new DateTimeImmutable();
    }

    public function type(): string {
        return 'article';
    }

    public function addTag(string ...$tags): static {
        foreach ($tags as $tag) {
            $this->tags[] = strtolower(trim($tag));
        }
        return $this;
    }

    public function render(): string {
        $heading = "#{$this->id} — {$this->name}";
        $tagList = implode(', ', $this->tags) ?: '(none)';

        return <<<HTML
        <article data-status="{$this->status->value}">
            <h1>{$heading}</h1>
            <p>Status: {$this->status->label()}</p>
            <footer>Tags: {$tagList}</footer>
        </article>
        HTML;
    }
}

// ---------------------------------------------------------------
// Functions, closures, generators
// ---------------------------------------------------------------

function fibonacci(int $n): \Generator {
    [$a, $b] = [0, 1];
    for ($i = 0; $i < $n; $i++) {
        yield $a;
        [$a, $b] = [$b, $a + $b];
    }
}

$double = fn(int $x): int => $x * 2;

$sum = static function (array $numbers): int|float {
    return array_reduce($numbers, fn($carry, $item) => $carry + $item, 0);
};

// ---------------------------------------------------------------
// Runtime demo
// ---------------------------------------------------------------

try {
    $article = (new Article(1, 'Hello, Afterglow', Status::Active))
        ->addTag('PHP', 'Theme', 'Demo');

    $fibs = array_map($double, iterator_to_array(fibonacci(8)));

    printf("App: %s%s", APP_NAME, PHP_EOL);
    printf("Fibs × 2: [%s]%s", implode(', ', $fibs), PHP_EOL);
    printf("Sum: %d%s", $sum($fibs), PHP_EOL);

    echo $article->render(), PHP_EOL;
    echo json_encode($article, JSON_PRETTY_PRINT), PHP_EOL;
} catch (RuntimeException $e) {
    fwrite(STDERR, "Error: {$e->getMessage()}" . PHP_EOL);
    exit(1);
}
?>
<!-- Inline HTML mode to show mixed-language highlighting -->
<!DOCTYPE html>
<html lang="en">
<body>
    <p>Rendered by <?= APP_NAME ?> at <?php echo date('Y-m-d H:i'); ?></p>
</body>
</html>
