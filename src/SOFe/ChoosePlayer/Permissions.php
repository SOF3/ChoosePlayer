<?php
namespace SOFe\ChoosePlayer;
final class Permissions {
    /**
     * Allows selecting online players.
     */
    public const CHOOSE_PLAYER_ONLINE_SELECTOR = "ChoosePlayer.OnlineSelector";
    /**
     * Allows searching offline players by name.
     */
    public const CHOOSE_PLAYER_OFFLINE_SELECTOR = "ChoosePlayer.OfflineSelector";
    /**
     * Allows selecting recently-seen offline players.
     */
    public const CHOOSE_PLAYER_RECENT_SELECTOR = "ChoosePlayer.RecentSelector";
}