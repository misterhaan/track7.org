<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/page.php';
require_once 'user.php';

class UserProfile extends Page {
	private static ?DetailedUser $profile;

	public function __construct() {
		self::$profile = DetailedUser::FromQueryString(self::RequireDatabase(), self::RequireUser());
		if (!self::$profile)
			self::Redirect(dirname($_SERVER['PHP_SELF']) . '/');

		parent::__construct(self::$profile->DisplayName);
	}

	protected static function MainContent(): void {
		self::WriteHeader();
		self::WriteActions();
		self::WriteContacts();
		self::WriteRankings();
		self::WriteActivity();
	}

	private static function WriteHeader(): void {
?>
		<header class=profile>
			<img class=avatar src="<?= htmlspecialchars(self::$profile->Avatar); ?>" alt="">
			<div>
				<h1 <?= self::$profile->Friend ? ' class=friend title="' . htmlspecialchars(self::$profile->DisplayName) . ' is your friend"' : ''; ?>><?= htmlspecialchars(self::$profile->DisplayName); ?></h1>
				<p>
					<?= htmlspecialchars(self::$profile->LevelName); ?>, joined
					<time datetime="<?= htmlspecialchars(self::$profile->Registered->DateTime); ?>" title="<?= htmlspecialchars(self::$profile->Registered->Tooltip); ?>"><?= htmlspecialchars(self::$profile->Registered->Display); ?> ago</time>
				</p>
			</div>
		</header>

	<?php
	}

	private static function WriteActions(): void {
	?>
		<nav class=actions>
			<?php
			if (self::$profile->ID == self::$user->ID) {
			?>
				<a class=edit title="edit your profile" href="/user/settings.php">edit profile</a>
			<?php
			} else {
			?>
				<a class=sendmessage title="send <?= htmlspecialchars(self::$profile->DisplayName); ?> a private message" href="/user/messages.php#!to=<?= htmlspecialchars(self::$profile->Username); ?>">send message</a>
				<?php
				if (self::$profile->Friend) {
				?>
					<a class=removefriend title="remove <?= htmlspecialchars(self::$profile->DisplayName); ?> from your friends" href="/api/user.php/friend/<?= htmlspecialchars(self::$profile->ID); ?>">remove friend</a>
				<?php
				} elseif (self::IsUserLoggedIn()) {
				?>
					<a class=addfriend title="add <?= htmlspecialchars(self::$profile->DisplayName); ?> as a friend" href="/api/user.php/friend/<?= htmlspecialchars(self::$profile->ID); ?>">add friend</a>
			<?php
				}
			}
			?>
		</nav>

	<?php
	}

	private static function WriteContacts(): void {
	?>
		<section id=contacts></section>

		<?php
	}

	private static function WriteRankings(): void {
		if (self::$profile->Fans->Count + self::$profile->Posts->Count + self::$profile->Comments->Count) {
		?>
			<section id=rank>
				<header>rankings</header>
				<ul>
					<?php
					if (self::$profile->Fans->Count) {
					?>
						<li>#<?= +self::$profile->Fans->Rank ?> in fans with <?= +self::$profile->Fans->Count; ?></li>
					<?php
					}
					if (self::$profile->Posts->Count) {
					?>
						<li>#<?= +self::$profile->Posts->Rank ?> in posts with <?= +self::$profile->Posts->Count; ?></li>
					<?php
					}
					if (self::$profile->Comments->Count) {
					?>
						<li>#<?= +self::$profile->Comments->Rank; ?> in <a href="comments" title="view all of <?= self::$profile->DisplayName; ?>’s comments">comments</a> with <?= +self::$profile->Comments->Count; ?></li>
					<?php
					}
					?>
				</ul>
			</section>
		<?php
		}
	}

	private static function WriteActivity(): void {
		?>
		<section id=activity></section>

<?php
	}
}
new UserProfile();
