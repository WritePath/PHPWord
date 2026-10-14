# WritePath PHPWord fork

## Fork changes

### Configurable HTML list styles

The fork extends `Shared\Html::getListStyle()` to accept custom numbering
definitions through the fifth argument of `Html::addHtml()`:

```php
\PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, true, [
    'LIST_STYLES' => [
        'multilevel' => [
            'type' => 'multilevel',
            'levels' => [
                ['format' => 'upperRoman', 'text' => '%1)', 'left' => 960],
            ],
        ],
        'hybridMultilevel' => [
            'type' => 'hybridMultilevel',
            'levels' => [
                ['format' => 'bullet', 'text' => '◆', 'left' => 480],
            ],
        ],
    ],
]);
```

`multilevel` controls ordered lists; `hybridMultilevel` controls unordered lists.
Each supplied definition replaces the corresponding default definition rather
than merging with it. Supply the levels needed for nested lists. If an option
is absent, PHPWord uses its built-in definition. A subsequent `addHtml()` call
without these options also uses the defaults.

Source: `src/PhpWord/Shared/Html.php`.

### DOCX diagonal cell borders

The fork adds `getDiagonal()` and `setDiagonal()` to `Style\Cell`, together with
Word2007 reader and writer support:

```php
$table->addCell(2000, ['diagonal' => 'down']);
```

| Value | DOCX element | Direction |
| --- | --- | --- |
| `down` | `w:tl2br` | Top-left to bottom-right |
| `up` | `w:tr2bl` | Top-right to bottom-left |
| `null` | Neither | No diagonal border emitted |

The writer emits native borders in `w:tcBorders`, without post-processing
`document.xml`. It uses a single line with `w:sz="4"` (0.5 pt), `w:space="0"`,
and `Cell::DEFAULT_BORDER_COLOR` (`000000`, black).

The reader detects these elements in a cell's explicit `w:tcPr/w:tcBorders`
and restores the direction. It preserves only the direction, not the original
diagonal border color, width, or line style. An element whose `w:val` is `nil`
or `none` is ignored, because it means "no border". If both directions are
present, `down` takes precedence; the current property cannot represent both
simultaneously.

Source: `Style\Cell`, `Reader\Word2007\AbstractPart`, and
`Writer\Word2007\Style\Cell`.

## Updating from upstream

Start with a clean working tree. Keep `upstream` configured for future updates;
add it once if it does not exist:

```bash
git remote add upstream git@github.com:PHPOffice/PHPWord.git
```

Create a local backup and rebase the fork directly onto upstream:

```bash
git switch writepath
phpword_backup="backup/writepath-before-sync-$(date +%Y%m%d-%H%M%S)"
git branch "$phpword_backup"
git fetch upstream
phpword_old_base=$(git merge-base HEAD upstream/master)
git rebase upstream/master
```

Resolve any conflicts while preserving the fork's features, stage the resolutions,
and use `git rebase --continue`. Use `git rebase --abort` to cancel an in-progress
rebase.

Review the replayed changes and validate the result:

```bash
git range-diff "$phpword_old_base..$phpword_backup" "upstream/master..writepath"
git diff --check upstream/master..writepath
composer install
composer validate --strict
composer check
```

Inspect the list-style and diagonal-border regression tests in particular.
An upstream update changes the final tree, so comparing the old and new trees
for equality is not a valid synchronization check. `range-diff` helps review
how the fork's own patches changed during the rebase.

When publishing is authorized, push the rewritten branch with a lease:

```bash
git push --force-with-lease origin writepath
```

Updating the fork's `master` branch is a separate maintenance operation and is
not required to update `writepath`.
