<?php

namespace Prim\Velvet;

use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockToolType;
use pocketmine\block\utils\TreeType;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\ItemFactory;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\World;
use Prim\Velvet\Anticheat\AntiCheatManager;
use Prim\Velvet\Blocks\Leaves;
use Prim\Velvet\Commands\Main\DiscordCommand;
use Prim\Velvet\Commands\Main\DuelCommand;
use Prim\Velvet\Commands\Main\EmoteCommand;
use Prim\Velvet\Commands\Main\NickCommand;
use Prim\Velvet\Commands\Main\NightVisionCommand;
use Prim\Velvet\Commands\Main\PingCommand;
use Prim\Velvet\Commands\Main\RulesCommand;
use Prim\Velvet\Commands\Main\ScoreboardCommand;
use Prim\Velvet\Commands\Main\SettingsCommand;
use Prim\Velvet\Commands\Main\SpawnCommand;
use Prim\Velvet\Commands\Main\StatsCommand;
use Prim\Velvet\Commands\Main\StealSkinCommand;
use Prim\Velvet\Commands\Main\SuggestCommand;
use Prim\Velvet\Commands\Main\TellCommand;
use Prim\Velvet\Commands\Owner\LeaderboardCommand;
use Prim\Velvet\Commands\Owner\OPCommand;
use Prim\Velvet\Commands\Owner\SetKillstreakCommand;
use Prim\Velvet\Commands\Owner\StopCommand;
use Prim\Velvet\Commands\Owner\TestCommand;
use Prim\Velvet\Commands\Permissions\AddRankCommand;
use Prim\Velvet\Commands\Permissions\Chat\ClearPrefixCommand;
use Prim\Velvet\Commands\Permissions\Chat\SetPrefixCommand;
use Prim\Velvet\Commands\Permissions\DefRankCommand;
use Prim\Velvet\Commands\Permissions\DelRankCommand;
use Prim\Velvet\Commands\Permissions\ListGPermsCommand;
use Prim\Velvet\Commands\Permissions\ListUPermsCommand;
use Prim\Velvet\Commands\Permissions\RankInfoCommand;
use Prim\Velvet\Commands\Permissions\RanksCommand;
use Prim\Velvet\Commands\Permissions\SetGPermCommand;
use Prim\Velvet\Commands\Permissions\SetRankCommand;
use Prim\Velvet\Commands\Permissions\SetUPermCommand;
use Prim\Velvet\Commands\Permissions\UnsetGPermCommand;
use Prim\Velvet\Commands\Permissions\UnsetUPermCommand;
use Prim\Velvet\Commands\Staff\AliasCommand;
use Prim\Velvet\Commands\Staff\BanCommand;
use Prim\Velvet\Commands\Staff\BlacklistCommand;
use Prim\Velvet\Commands\Staff\BuildCommand;
use Prim\Velvet\Commands\Staff\EventCommand;
use Prim\Velvet\Commands\Staff\ForceTellCommand;
use Prim\Velvet\Commands\Staff\FreezeCommand;
use Prim\Velvet\Commands\Staff\KickCommand;
use Prim\Velvet\Commands\Staff\PlayerInfoCommand;
use Prim\Velvet\Commands\Staff\ResetBuildCommand;
use Prim\Velvet\Commands\Staff\SocialSpyCommand;
use Prim\Velvet\Commands\Staff\SudoCommand;
use Prim\Velvet\Commands\Staff\UnbanCommand;
use Prim\Velvet\Commands\Staff\VanishCommand;
use Prim\Velvet\Commands\World\MultiWorldCommand;
use Prim\Velvet\Duels\ArenaManager;
use Prim\Velvet\Duels\MatchManager;
use Prim\Velvet\Duels\Parties\PartyManager;
use Prim\Velvet\Duels\QueueManager;
use Prim\Velvet\Entities\SplashPotion;
use Prim\Velvet\Games\Game;
use Prim\Velvet\Items\EnderPearl as PearlItem;
use Prim\Velvet\Entities\EnderPearl as PearlEntity;
use Prim\Velvet\Items\SplashPotion as PotionItem;
use Prim\Velvet\Managers\CosmeticManager;
use Prim\Velvet\Managers\EntityManager;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Tasks\AsyncInitLeaderboardTask;
use Prim\Velvet\Tasks\ClearEntitiesTask;
use Prim\Velvet\Tasks\MainTask;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Tasks\ScoretagTask;
use Prim\Velvet\Utils\Enchants;
use Prim\Velvet\Utils\Forms;
use Prim\Velvet\Utils\Kits;
use Prim\Velvet\Utils\NewPacket;
use Prim\Velvet\Utils\Scoreboard;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\Worlds\VoidGenerator;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function str_repeat;
use function substr_replace;

class Main extends PluginBase {

	public static self $instance;

	public array $taggedPlayers = [];
	/** @var array<string, TaskHandler> */
	public array $scoreboards = [];
	public array $titleIds = [];
	public array $suggestCooldown = [];

	public Kits $kits;
	public Forms $forms;
	public Enchants $enchants;
	public ?Game $game = null;

	public QueueManager $queueManager;
	public MatchManager $matchManager;
	public ArenaManager $arenaManager;
	public SessionManager $sessionManager;
	public PartyManager $partyManager;
	public CosmeticManager $cosmeticManager;
	public EntityManager $entityManager;
	public PermissionManager $permissionManager;
	public AntiCheatManager $antiCheatManager;

	public function onLoad() : void {
		GeneratorManager::getInstance()->addGenerator(VoidGenerator::class, 'void', fn() => null);
	}

	public function onEnable() : void {
		PacketPool::getInstance()->registerPacket(new NewPacket()); // todo: remove this when pm uses the latest BedrockProtocol
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->init();
	}

	public function init() : void {
		self::$instance = $this;
		Scoreboard::$instance = new Scoreboard();
		$this->initfiles();
		$this->unregisterCommands();
		$this->registerCommands();
		$this->registerCustom();

		foreach (['nodebuff', 'gapple', 'pvp', 'ffa', 'god', 'duels', 'sumoduels', 'botduels', 'godduels', 'oitc'] as $level){
			$this->getServer()->getWorldManager()->loadWorld($level);
		}

		$this->kits = new Kits;
		$this->forms = new Forms;
		$this->enchants = new Enchants($this);
		$this->entityManager = new EntityManager($this);
		$this->queueManager = new QueueManager($this);
		$this->matchManager = new MatchManager($this);
		$this->arenaManager = new ArenaManager;
		$this->sessionManager = new SessionManager;
		$this->partyManager = new PartyManager;
		$this->cosmeticManager = new CosmeticManager($this);
		$this->permissionManager = new PermissionManager;
		$this->antiCheatManager = new AntiCheatManager;
		$this->registerArenas();

		$this->getScheduler()->scheduleRepeatingTask(new MainTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new ScoretagTask($this), 10);
		$this->getScheduler()->scheduleRepeatingTask(new ClearEntitiesTask, 6000);
		$this->getServer()->getAsyncPool()->submitTask(new AsyncInitLeaderboardTask($this->getDataFolder() . 'data/'));
	}

	public function initfiles() : void {
		foreach(['data', 'alias', 'capes'] as $folder) {
			if(!is_dir($this->getDataFolder() . $folder)){
				mkdir($this->getDataFolder() . $folder);
			}
		}

		$this->saveDefaultConfig();
		foreach(['models.yml', 'arenas.json', 'leaderboards.json', 'ranks.json'] as $file){
			$this->saveResource($file);
		}
	}

	public function registerCommands() : void {
		$this->getServer()->getCommandMap()->registerAll($this->getName(), [
			new MultiWorldCommand,
			new SpawnCommand($this),
			new AddRankCommand,
			new DefRankCommand,
			new DelRankCommand,
			new ListGPermsCommand,
			new ListUPermsCommand,
			new RankInfoCommand,
			new RanksCommand,
			new SetGPermCommand,
			new SetRankCommand,
			new SetUPermCommand,
			new UnsetGPermCommand,
			new UnsetUPermCommand,
			new StopCommand,
			new OPCommand,
			new TestCommand,
			new SetKillstreakCommand,
			new SuggestCommand($this),
			new SetPrefixCommand,
			new ClearPrefixCommand,
			new VanishCommand,
			new SudoCommand,
			new StatsCommand,
			new SettingsCommand,
			new ScoreboardCommand($this),
			new RulesCommand,
			new PlayerInfoCommand,
			new PingCommand,
			new NightVisionCommand,
			new TellCommand,
			new LeaderboardCommand,
			new EventCommand($this),
			new EmoteCommand,
			new DuelCommand($this),
			new DiscordCommand,
			new BuildCommand,
			new UnbanCommand,
			new StealSkinCommand,
			new SocialSpyCommand,
			new BlacklistCommand,
			new KickCommand,
			new FreezeCommand,
			new ForceTellCommand,
			new AliasCommand,
			new BanCommand,
			new NickCommand,
			new ResetBuildCommand
		]);
	}

	public function unregisterCommands() : void {
		$commandMap = $this->getServer()->getInstance()->getCommandMap();
		$commands = ['kick', 'me', 'ban', 'pardon', 'kill', 'tell', 'difficulty', 'particle', 'seed', 'title', 'op', 'stop', 'clear', 'checkperm'];
		foreach($commandMap->getCommands() as $cmd){
			if(in_array($cmd->getName(), $commands)){
				$cmd->setLabel('disabled_' . $cmd->getName());
				$commandMap->unregister($cmd);
			}
		}
	}

	public function registerCustom() : void {
		ItemFactory::getInstance()->register(new PearlItem, true);
		ItemFactory::getInstance()->register(new PotionItem, true);

		$entityFactory = EntityFactory::getInstance();
		$entityFactory->register(PearlEntity::class, function (World $world, CompoundTag $nbt) : PearlEntity {
			return new PearlEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['ThrownEnderpearl', 'minecraft:ender_pearl'], EntityLegacyIds::ENDER_PEARL);
		$entityFactory->register(SplashPotion::class, function(World $world, CompoundTag $nbt) : SplashPotion{
			$potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort("PotionId", PotionTypeIds::STRONG_HEALING));
			return new SplashPotion(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);
		}, ['ThrownPotion', 'minecraft:potion', 'thrownpotion'], EntityLegacyIds::SPLASH_POTION);

		$blockFactory = BlockFactory::getInstance();
		$leavesBreakInfo = new BlockBreakInfo(0.2, BlockToolType::SHEARS);
		foreach(TreeType::getAll() as $treeType) {
			$magicNumber = $treeType->getMagicNumber();
			$blockFactory->register(new Leaves(new BlockIdentifier($magicNumber >= 4 ? BlockLegacyIds::LEAVES2 : BlockLegacyIds::LEAVES, $magicNumber & 0x03), $treeType->getDisplayName() . ' Leaves', $leavesBreakInfo, $treeType), true);
		}

		EnchantmentIdMap::getInstance()->register(-1, new Enchantment('glow', -1, ItemFlags::ALL, ItemFlags::NONE, 1));
	}

	public static function getMain() : self {
		return self::$instance;
	}

	public function registerCPS(VelvetPlayer $player) : void {
		$player->addClick();
		$player->sendTip("§dCPS §b" . $player->getClicks());
	}

	public function setTagged(string|Player $name, bool $value = true, int $time = 15) : void {
		if($name instanceof Player) $name = $name->getName();
		if($value){
			$this->taggedPlayers[$name] = $time;
		} else {
			unset($this->taggedPlayers[$name]);
		}
	}

	public function isTagged(string|Player $name) : bool {
		return isset($this->taggedPlayers[($name instanceof Player ? $name->getName() : $name)]);
	}

	public function registerArenas() : void {
		$am = $this->arenaManager;
		$serv = $this->getServer();
		$file = json_decode(file_get_contents($this->getDataFolder() . 'arenas.json'), true);
		foreach($file["arenas"] as $name => $arena){
			if(!isset($arena['name'], $arena['mode'], $arena['spawn1'], $arena['spawn2'], $arena['level'])){
				$this->getLogger()->error('Failed to load' . ($arena['name'] ?? 'Unknown'));
				continue;
			}
			$s1 = $arena['spawn1'];
			$s2 = $arena['spawn2'];
			if($s1 !== null && $s2 !== null){
				if(!$serv->getWorldManager()->isWorldLoaded($arena['level'])){
					$serv->getWorldManager()->loadWorld($arena['level']);
				}
				$level = $serv->getWorldManager()->getWorldByName($arena['level']);
				if($level !== null){
					$am->registerArena($name, $arena['mode'], new Vector3($s1[0], $s1[1], $s1[2]), new Vector3($s2[0], $s2[1], $s2[2]), $level);
				} else {
					$this->getLogger()->error("Failed to register {$arena['name']} because the level is invalid.");
				}
			} else {
				$this->getLogger()->error("Failed to register {$arena['name']} because positions were formatted incorrectly.");
			}
		}
	}

	public function scoretag(VelvetPlayer $player) : void {
		$player->setScoreTag("{$this->health($player)} " . TF::AQUA . "CPS: {$player->getClicks()}\n" . TF::GOLD . Translator::SYSTEMS[$player->deviceOS]);
	}

	public function health(Player $player) : string {
		return ($player->getEffects()->has(VanillaEffects::POISON()) ? TF::YELLOW : (($player->getEffects()->has(VanillaEffects::WITHER())) ? TF::LIGHT_PURPLE : TF::GREEN)) . ($player->getHealth() < $player->getMaxHealth() ? (substr_replace(str_repeat('|', $player->getMaxHealth()), TF::RED, (int) $player->getHealth() - 1, 0)) : str_repeat('|', $player->getMaxHealth())) . ($player->getAbsorption() > 0 ? TF::GOLD . str_repeat('|', (int) $player->getAbsorption()) : '');
	}

	public function getDataPath(Player $player) : string {
		return $this->getDataFolder() . 'data/' . $player->getXuid() . '.json';
	}

	public function getData(Player $player){
		return json_decode(file_get_contents($this->getDataPath($player)), true);
	}

	public function isRegistered(Player $player) : bool {
		return file_exists($this->getDataPath($player));
	}

	public function registerPlayer(Player $player) : void {
		$data = [
			'name' => $player->getName(),
			'pot-color' => [248, 36, 35],
			'kills' => 0,
			'deaths' => 0,
			'killstreak' => 0,
			'topkillstreak' => 0,
			'igns' => [$player->getName()]
		];
		file_put_contents($this->getDataPath($player), json_encode($data, JSON_PRETTY_PRINT));
	}

	public function isInBuild(Player $player, Vector3 $position) : bool {
		return (new AxisAlignedBB(260, 64, 145, 382, 107, 257))->isVectorInside($position) && $player->getWorld()->getFolderName() === Translator::BUILDUHC_WORLD && !$this->isInside2D(317, 324, 197, 204, $position);
	}

	public function isInGodSpawn(Entity $player) : bool {
		return (new AxisAlignedBB(299, 40, 196, 304, 45, 201))->isVectorInside($player->getPosition());
	}

	public function isInside2D(int $minX, int $maxX, int $minZ, int $maxZ, Vector3 $position) : bool {
		if($position->x <= $minX or $position->x >= $maxX) return false;
		return $position->z > $minZ and $position->z < $maxZ;
	}

	public function setPotionColor(Player $player, array $colors){
		$this->sessionManager->getSession($player)->potColor = $colors;
	}

}