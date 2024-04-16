<?php

namespace Prim\Velvet\Utils;

use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\utils\TextFormat as TF;

interface Translator {

	const NO_PERMISSION = TF::DARK_RED . "You do not have permission to use this command!";
	const INGAME_ONLY = TF::DARK_RED . "Use this command in-game!";
	const RANK_DOESNT_EXIST = self::RANKS_PREFIX . TF::RED . 'That rank does not exist!';
	const NEVER_JOINED = TF::RED . 'That player has never joined before!';

	const WORLDS_PREFIX = TF::GOLD . 'Worlds » ';
	const RANKS_PREFIX = TF::GOLD . 'Ranks » ';
	const VELVET_PREFIX = TF::DARK_GRAY . '[' . TF::DARK_PURPLE . 'Velvet' . TF::DARK_GRAY . '] ' . TF::LIGHT_PURPLE;
	const DISCORD_LINK = 'discord.gg/yYpPX3d';
	const SHOP_LINK = 'velvet.tebex.io';
	const OWNER_XUID = '2535468189561157';
	const OWNER_NAME = 'xPrim69x';
	const SERVER_ADDRESS = 'velvetpractice.tk';

	const AUTOBAN_WEBHOOK = 'https://discord.com/api/webhooks/815000375018913812/ucIhOrbdGwD8UN8DRfRAP1uJk84KxY4afcpS2-Ldpwz_Fu1EK62xZWQDvRU1peKy1Lkj';
	const COMMANDS_WEBHOOK = 'https://discord.com/api/webhooks/856391903561121812/EG83UbY3eP4-N1BwQOaI7G84AHPBeqi8Ym1SeHcTA_GJ8tnHsvsA5iHGzXWtbLlcwoB2';
	const TITLEID_WEBHOOK = 'https://discord.com/api/webhooks/905288033887215616/IQjFfmIrJsqDWidxcS64gy5yCNL_wh2o1OxGJaBFQ3CfGXShWiNpWM6-YMNB4si8AdiC';
	const ANTICHEAT_WEBHOOK = 'https://discord.com/api/webhooks/767569955348283402/RAVAUKOYGEokCM4Tw_OynnyXPIIhVeDaChbLo5X8_3dYHX9mwrljo9oZdQIEfff6V8ts';

	const CHAT_COOLDOWN = 2;
	const PEARL_COOLDOWN = 10;
	const GAPPLE_COOLDOWN = 7;

	const PARTY_SIZES = ['VIP' => 10, 'YouTuber' => 10, 'Ravager' => 12, 'Famous' => 12, 'Hyperedge' => 14];
	const PARTY_MODES = ['NoDebuff', 'Gapple', 'God', 'Sumo'];

	const DUEL_MODES = ['NoDebuff', 'Gapple', 'Diamond', 'God', 'Sumo', 'Line'];
	const BOT_MODES = ['Clown', 'Easy', 'Medium', 'Hard', 'Cheater'];

	const BOT_REACH = ['Clown' => 1.6, 'Easy' => 2.3, 'Medium' => 2.7, 'Hard' => 3, 'Cheater' => 4.5];
	const BOT_BLOCKS_RUN_TO_POT = ['Clown' => 4, 'Easy' => 3, 'Medium' => 2.57, 'Hard' => 2, 'Cheater' => 1.3];
	const BOT_DAMAGE = ['Clown' => 0, 'Easy' => 1, 'Medium' => 1.5, 'Hard' => 2, 'Cheater' => 2.5];

	const SERVER_CLOSED = TF::YELLOW . 'Server Closed';
	const SERVER_RESTARTING = TF::YELLOW . 'The server is restarting!';

	const CANNOT_USE_QUEUED = TF::RED . 'You cannot use this while queued!';
	const CANNOT_USE_PARTY = TF::RED . 'You cannot use this while in a party!';
	const CANNOT_USE_QUEUED_OR_PARTY = TF::RED . 'You cannot use this while queued or in a party!';

	const COMMAND_STAFF = TF::GREEN . ' (Staff!)';

	const BLACKLISTED_MESSAGE = 'You are blacklisted!';

	const SCALES = [0.4, 0.7, 1, 1.5, 2, 3.2];
	const SIZES = ['Extra Small', 'Small', 'Normal', 'Medium', 'Large', 'Extra Large'];

	const FORMS = [
		'§aArenas!' => 'form', '§3Duels!' => 'duels', '§6Bot Fights!' => 'botduels',
		'§dProfile!' => 'stats', '§dParty!' => 'partyForm', '§9Toys!' => 'toys', '§gEvents!' => 'showEvents'
	];

	const STAFF_RANKS = ['Trainee', 'Moderator', 'Admin', 'Owner'];
	const STAFF_NAMES = ['xprim69x', 'creepergamin113', 'iithzwing', 'b1az3z', 'illuminosx4', 'kyracle', 'xryze1', 'ty breezin', 'xliekq', 'facqde', 'thefallenone177', 'lime9912'];

	const EMOTES = [
		'4c8ae710-df2e-47cd-814d-cc7bf21a3d67' => 'Wave',
		'9a469a61-c83b-4ba9-b507-bdbe64430582' => 'Simple Clap',
		'ce5c0300-7f03-455d-aaf1-352e4927b54d' => 'Over There!',
		'd7519b5a-45ec-4d27-997c-89d402c6b57f' => 'The Pickaxe',
		'86b34976-8f41-475b-a386-385080dc6e83' => 'Diamonds To You!',
		'7cec98d8-55cc-44fe-b0ae-2672b0b2bd37' => 'The Hammer',
		'6d9f24c0-6246-4c92-8169-4648d1981cbb' => 'Faceplant',
		'42fde774-37d4-4422-b374-89ff13a6535a' => 'The Woodpunch',
		'18891e6c-bb3d-47f6-bc15-265605d86525' => 'Abduction?',
		'efc2f0f5-af00-4d9e-a4b1-78f18d63be79' => 'Fake Death',
		'05af18ca-920f-4232-83cb-133b2d913dd6' => 'Underwater Dancing',
		'5dd129f9-cfc3-4fc1-b464-c66a03061545' => 'Hand Stand',
		'f1e18201-729d-472d-9e4f-5cdd7f6bba0c' => 'Shy Giggling',
		'85957448-e7bb-4bb4-9182-510b4428e52c' => 'Meditating Like Luke' . TF::GREEN . ' (Special)',
		'1dbaa006-0ec6-42c3-9440-a3bfa0c6fdbe' => 'Breakdance',
		'21e0054a-5bf4-468d-bfc4-fc4b49bd44ac' => 'Offering',
		'7393aa53-9145-4e66-b23b-ec86def6c6f2' => 'The Elytra',
		'e1090020-cbe0-4b64-9c41-a3b9619da029' => 'Giving R2-D2 A Message' . TF::GREEN . ' (Special)',
		'5a5b2c0c-a924-4e13-a99b-4c12e3f02e1e' => 'Ghast Dance',
		'5d644007-3cdf-4246-b4ca-cfd7a4318a1c' => 'Playing Zombie',
		'98a68056-e025-4c0f-a959-d6e330ccb5f5' => 'Sad Sigh',
		'daeaaa6f-db91-4461-8617-400c5d1b8646' => 'Surrendering',
		'ddfa6f0e-88ca-46de-b189-2bd5b18e96a0' => 'Bow',
		'4ff73ed2-3c2f-4d74-9055-5fa24e59dc7a' => 'Shrug',
		'a98ea25e-4e6a-477f-8fc2-9e8a18ab7004' => 'Disappointed',
		'402efb2d-6607-47f2-b8e5-bc422bcd8304' => 'Facepalm',
		'a602063f-1ded-4959-b978-b5ae7f353536' => 'Rebooting',
		'f99ccd35-ebda-4122-b458-ff8c9f9a432f' => 'Cowpoke Dancin\'',
		'434489fd-ed42-4814-961a-df14161d67e0' => 'Golf Clap',
		'13334afa-bd66-4285-b3d9-d974046db479' => 'Foot Stomp!',
		'7a314ecf-f94c-42c0-945f-76903c923808' => 'Bored',
		'819f2f36-2a16-440c-8e46-94c6b003a2e0' => 'Big Chuckles',
		'f9345ebb-4ba3-40e6-ad9b-6bfb10c92890' => 'Ahh Choo!',
		'a12252fa-4ec8-42e0-a7d0-d44fbc90d753' => 'Dancing Like Toothless' . TF::GREEN . ' (Special)',
		'd0c60245-538e-4ea2-bdd4-33477db5aa89' => 'Victory Cheer',
		'c2a47805-c792-4882-a56d-17c80b6c57a8' => 'Acting Like A Dragon' . TF::GREEN . ' (Special)',
		'59d9e78c-f0bb-4f14-9e9b-7ab4f58ffbf5' => 'Chatting',
		'738497ce-539f-4e06-9a03-dc528506a468' => 'Giddy',
		'4b9b9f17-3722-4d38-a6a9-9ba0e8cf5044' => 'Using Jedi Mind Trick' . TF::GREEN . ' (Special)',
		'71721c51-b7d1-46b1-b7ea-eb4c4126c3db' => 'Over Here',
		'79452f7e-ffa0-470f-8283-f5063348471d' => 'Ballerina Twirl',
		'd863b9cc-9f8c-498b-a8a3-7ebd542cb08e' => 'Groovin\'',
		'6f82688e-e549-408c-946d-f8e99b91808d' => 'ROFL',
		'9f5d4732-0513-4a0a-8ea2-b6b8d7587e74' => 'Calling A Dragon' . TF::GREEN . ' (Special)',
		'c2d6091d-9f91-4a9e-badd-ef8481353cb0' => 'Waving Like C-3PO' . TF::GREEN . ' (Special)',
		'20bcb500-af82-4c2f-9239-e78191c61375' => 'Thinking',
		'bb6f1764-2b0b-4a3a-adfd-3334627cdee4' => 'Feeling Sick'
	];

	const TELEPORTATIONS = [
		'mountain' => [300, 79, 143],
		'hill 1' => [276, 66, 205],
		'hill 2' => [238, 66, 162],
		'skybox 1' => [258, 84, 164],
		'skybox 2' => [273, 90, 203]
	];

	const SYSTEMS = [
		DeviceOS::UNKNOWN => '???',
		DeviceOS::ANDROID => 'Android',
		DeviceOS::IOS => 'IOS',
		DeviceOS::OSX => 'MacOS',
		DeviceOS::AMAZON => 'FireOS',
		DeviceOS::GEAR_VR => 'GearVR',
		DeviceOS::WIN32 => 'Win32',
		DeviceOS::WINDOWS_10 => 'Win10',
		DeviceOS::DEDICATED => 'Dedicated',
		DeviceOS::TVOS => 'TV',
		DeviceOS::PLAYSTATION => 'PS4',
		DeviceOS::NINTENDO => 'Switch',
		DeviceOS::XBOX => 'Xbox',
		DeviceOS::WINDOWS_PHONE => 'Windows Phone'
	];

	const CONTROLS = ['Unknown', 'Mouse', 'Touch', 'Controller'];
	const MOBILE = [DeviceOS::ANDROID, DeviceOS::AMAZON, DeviceOS::IOS, DeviceOS::WINDOWS_PHONE];

	const MESSAGES = [
		'Join the discord at discord.gg/yYpPX3d!',
		'Make sure to read the rules! /rules.',
		"Don't 2v1 or interfere, it can lead to a kick or temporary ban if excessive.",
		'Buy a rank at velvet.tebex.io!',
		'Irregular clicking methods such as drag clicking, bolt clicking, etc, are not allowed! Your debounce time should be no less than 10ms! Do /rules to read the rules.',
		"Can't see in the dark? /nightvision or /nv!",
		'Have any suggestions? Use /suggest to have your suggestion sent to the Velvet discord!'
	];

	const DEATH_MESSAGES = [
		'railed', 'quickied', 'clowned', 'given an L', 'harassed', 'exterminated', 'taken to the nose surgeon',
		'sent on a flight', 'banished', 'caught simping', 'eradicated', 'erased', 'folded', "pepega'd", 'sent to the moon',
		'touched', 'blown into outer space', 'whipped', 'rocked', 'terrorized', 'tortured', 'beaten up', 'bullied', 'punished',
		'abused'
	];

	const NODEBUFF_KB = [self::NODEBUFF_WORLD, self::DUELS_WORLD];

	const BANNED_COMMANDS = ['hub', 'spawn'];
	const FFA_WORLDS = [self::NODEBUFF_WORLD, self::GAPPLE_WORLD, self::DIAMOND_WORLD, self::BUILDUHC_WORLD, self::GOD_WORLD, self::OITC_WORLD, self::LOBBY_WORLD];
	const CURRENTLY_PLAYING = '§l§3» §r§bCurrently playing: §9';

	const BUILDUHC_WORLD = 'ffa';
	const NODEBUFF_WORLD = 'nodebuff';
	const GAPPLE_WORLD = 'gapple';
	const DIAMOND_WORLD = 'pvp';
	const GOD_WORLD = 'god';
	const LOBBY_WORLD = 'world';
	const OITC_WORLD = 'oitc';
	const EVENTS_WORLD = 'events';
	const DUELS_WORLD = 'duels';
	const GOD_DUELS_WORLD = 'godduels';
	const BOT_DUELS_WORLD = 'botduels';

	const DEFAULT_RANK = 'Normie';
	const DEFAULT_RANK_CHAT = "§a[{kills}] §8[Normie] §r{prefix}§a{display_name} §b> §7{msg}";
	const DEFAULT_RANK_NAMETAG = "§8[Normie] §7{display_name}";

	const POT_COLORS = [[248, 36, 35], [0, 0, 255], [0, 255, 0], [255, 100, 0], [255, 255, 0], [160, 0, 255]];
	const POT_COLOR_BUTTONS = ['§fDefault', '§fBlue', '§fGreen', '§fOrange', '§fYellow', '§fPurple', '§fCustomize a color!'];

}