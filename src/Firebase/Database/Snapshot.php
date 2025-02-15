<?php

declare(strict_types=1);

namespace Kreait\Firebase\Database;

use Kreait\Firebase\Exception\InvalidArgumentException;

use function count;
use function is_array;
use function JmesPath\search;
use function str_replace;
use function trim;

/**
 * A Snapshot contains data from a database location.
 *
 * It is an immutable copy of the data at a database location. It cannot be modified and will never
 * change (to modify data, you always call the {@see Reference::set()}).
 *
 * You can extract the contents of the snapshot as a JavaScript object by calling
 * the {@see getValue()} method.
 *
 * Alternatively, you can traverse into the snapshot by calling {@see getChild()}
 * to return child snapshots (which you could then call {@see getValue()} on).
 *
 * @see https://firebase.google.com/docs/reference/js/firebase.database.DataSnapshot
 */
final class Snapshot
{
    private Reference $reference;

    /**
     * @var mixed mixed
     */
    private $value;

    /**
     * @internal
     *
     * @param mixed $value
     */
    public function __construct(Reference $reference, $value)
    {
        $this->reference = $reference;
        $this->value = $value;
    }

    /**
     * Returns the key (last part of the path) of the location of this Snapshot.
     *
     * The last token in a database location is considered its key. For example, "ada" is the key for
     * the /users/ada/ node. Accessing the key on any Snapshot will return the key for the
     * location that generated it. However, accessing the key on the root URL of a database
     * will return null.
     *
     * @see https://firebase.google.com/docs/reference/js/firebase.database.DataSnapshot#key
     */
    public function getKey(): ?string
    {
        return $this->reference->getKey();
    }

    /**
     * Returns the Reference for the location that generated this Snapshot.
     *
     * @see https://firebase.google.com/docs/reference/js/firebase.database.DataSnapshot#ref
     */
    public function getReference(): Reference
    {
        return $this->reference;
    }

    /**
     * Returns another Snapshot for the location at the specified relative path.
     *
     * Passing a relative path to the child() method of a Snapshot returns another Snapshot for the location
     * at the specified relative path. The relative path can either be a simple child name (e.g. "ada") or a
     * deeper, slash-separated path (e.g. "ada/name/first"). If the child location has no data, an empty
     * Snapshot (that is, a Snapshot whose value is null) is returned.
     *
     * @see https://firebase.google.com/docs/reference/js/firebase.database.DataSnapshot#child
     *
     * @throws InvalidArgumentException if the given child path is invalid
     */
    public function getChild(string $path): self
    {
        $path = trim($path, '/');
        $expression = '"' . str_replace('/', '"."', $path) . '"';

        $childValue = search($expression, $this->value);

        return new self($this->reference->getChild($path), $childValue);
    }

    /**
     * Returns true if this Snapshot contains any data.
     *
     * It is a convenience method for `$snapshot->getValue() !== null`.
     *
     * @see https://firebase.google.com/docs/reference/js/firebase.database.DataSnapshot#exists
     */
    public function exists(): bool
    {
        return $this->value !== null;
    }

    /**
     * Returns true if the specified child path has (non-null) data.
     *
     * @see https://firebase.google.com/docs/reference/js/firebase.database.DataSnapshot#hasChild
     */
    public function hasChild(string $path): bool
    {
        $path = trim($path, '/');
        $expression = '"' . str_replace('/', '"."', $path) . '"';

        return search($expression, $this->value) !== null;
    }

    /**
     * Returns true if the Snapshot has any child properties.
     *
     * You can use {@see hasChildren()} to determine if a Snappshot has any children. If it does,
     * you can enumerate them using foreach(). If it does not, then either this snapshot
     * contains a primitive value (which can be retrieved with {@see getValue()}) or
     * it is empty (in which case {@see getValue()} will return null).
     *
     * @see https://firebase.google.com/docs/reference/js/firebase.database.DataSnapshot#hasChildren
     */
    public function hasChildren(): bool
    {
        return is_array($this->value) && !empty($this->value);
    }

    /**
     * Returns the number of child properties of this Snapshot.
     *
     * @see https://firebase.google.com/docs/reference/js/firebase.database.DataSnapshot#numChildren
     */
    public function numChildren(): int
    {
        return is_array($this->value) ? count($this->value) : 0;
    }

    /**
     * Returns the data contained in this Snapshot.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
