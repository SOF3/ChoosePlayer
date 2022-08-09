# ChoosePlayer

API plugin for choosing a player interactively.

## API

### Let player select another player

This API method opens a dialog to let player `$chooser` choose a player,
then returns the name and UUID of the chosen player.

```php
public static function ChoosePlayer::chooseCallback(
    Player $chooser,
    Closure $onSelect,
    Closure $onCancel,
    Closure $filter = null,
    string $text = "",
) : ChoosePlayerResult;
```

`$filter` are suggestion filters.
The `Filters` helper class can be used for creating filters.

`ChoosePlayerResult` has two public fields: `string $name` and `string $uuid`.
`$name` is the name of the player (which may or may not be in the correct case),
and `$uuid` is the human-readable 36-character player UUID string.
The consistency between name and UUID are provided
on a best-effort basis by the suggester plugin,
but may be inaccurate if the suggester plugin is out of sync.

An example plugin that provides an op command:

```php
<?php

use pocketmine\command\{Command, CommandSender};
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

use SOFe\ChoosePlayer\{ChoosePlayer, ChoosePlayerResult};

class Main extends PluginBase {
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        if(!($sender instanceof Player)) {
            $sender->sendMessage("This command can only be used in-game");
            return true;
        }

        ChoosePlayer::chooseCallback($sender, function(ChoosePlayerResult $result) use($sender) : void {
            $chosenPlayerName = $result->name;
            $this->getServer()->addOp($chosenPlayerName);
            $sender->sendMessage("opped $chosenPlayerName");
        }, function() use($sender) : void {
            $sender->sendMessage("Operation cancelled");
        }, Filters::isnt($sender))

        return true;
    }
}
```

### Provide suggestions

Use this API method to provide more ways of selecting players.

```
public static function \SOFe\ChoosePlayer\ChoosePlayer::suggest(\SOFe\ChoosePlayer\Suggester $suggester) : void;
```

See [`OnlinePlayerSuggester`](src/online.php) for example usage.
A few points to note:

- `getId()` should return a fully-qualified, consistent, unique identifier,
    because it is saved in the usage history for players.
- `getDisplayName()` is used in the dialog where player selects the suggester.
    Feel free to decorate this name!
- `suggest` is an [async iterator](https://sof3.github.io/await-generator/traverser/async-iterators.html).
    - TLDR: Implement the method like a normal await-generator async function
        (it does not have to be async if you don't need to).
        Simply write `yield $suggestion => Await::VALUE` for each suggestion.
    - Only a small batch of suggestions (20 by default) are displayed at a time.
        if possible, try loading no more than 20 suggestions at a time.
    - ChoosePlayer throws a `TerminateSuggestionsException`
        in the async iterator when the player stops selecting.
        You may want to wrap your code with try-finally blocks
        if there are resources you need to close.

## Building

To compile this plugin to phar, you need to use Composer.

```
composer install
composer build
```

The output phar is generated in `hack/ChoosePlayer.phar`.

You can also find development builds in
[GitHub Actions](https://github.com/SOF3/ChoosePlayer/actions)
(click into a run page and scroll to bottom)
or on [Poggit](https://poggit.pmmp.io/ci/SOF3/ChoosePlayer/~).
