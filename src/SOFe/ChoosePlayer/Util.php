<?php

declare(strict_types=1);

namespace SOFe\ChoosePlayer;

use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\dktapps\pmforms\CustomForm;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\dktapps\pmforms\CustomFormResponse;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\dktapps\pmforms\element\CustomFormElement;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\dktapps\pmforms\MenuForm;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\dktapps\pmforms\MenuOption;
use Generator;
use pocketmine\player\Player;
use SOFe\ChoosePlayer\libs\_524de3f98c72a58e\SOFe\AwaitGenerator\Await;
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