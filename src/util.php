<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\CustomFormElement;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;
use function count;
use function is_int;

/**
 * @internal This is not part of the public API.
 */
final class Util {
    /**
     * @param list<MenuOption> $options
     * @return Generator<mixed, mixed, mixed, int|null>
     */
    public static function asyncMenuForm(Player $player, string $title, string $text, array $options) : Generator {
        $ret = yield from Await::promise(function($resolve) use ($player, $title, $text, $options) {
            $form = new MenuForm($title, $text, $options, fn($_, int $index) => $resolve($index), fn() => $resolve(null));
            $player->sendForm($form);
        });
        if (!is_int($ret) || $ret < 0 || $ret >= count($options)) {
            return null;
        }
        return $ret;
    }

    /**
     * @param array<CustomFormElement> $elements
     * @return Generator<mixed, mixed, mixed, ?CustomFormResponse>
     */
    public static function asyncCustomForm(Player $player, string $title, array $elements) : Generator {
        return yield from Await::promise(function($resolve) use ($player, $title, $elements) {
            $form = new CustomForm($title, $elements, fn($_, CustomFormResponse $resp) => $resolve($resp), fn() => $resolve(null));
            $player->sendForm($form);
        });
    }
}
