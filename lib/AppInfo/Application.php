<?php
/**
 * Nextcloud - passman
 *
 * @copyright Copyright (c) 2016, Sander Brand (brantje@gmail.com)
 * @copyright Copyright (c) 2016, Marcos Zuriaga Miguel (wolfi@wolfi.es)
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Passman\AppInfo;

use OC\Files\View;
use OC\ServerContainer;
use OCA\Passman\Controller\ShareController;
use OCA\Passman\Middleware\APIMiddleware;
use OCA\Passman\Middleware\ShareMiddleware;
use OCA\Passman\Notifier;
use OCA\Passman\Service\ActivityService;
use OCA\Passman\Service\CredentialService;
use OCA\Passman\Service\CronService;
use OCA\Passman\Service\FileService;
use OCA\Passman\Service\NotificationService;
use OCA\Passman\Service\SettingsService;
use OCA\Passman\Service\ShareService;
use OCA\Passman\Service\VaultService;
use OCA\Passman\Utility\Utils;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Notification\IManager;
use OCP\Util;
use Psr\Container\ContainerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'passman';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$this->registerNavigationEntry();
		$this->registerPersonalPage();

		$context->registerEventListener(
			BeforeUserDeletedEvent::class,
			UserDeletedListener::class
		);


		$context->registerService(View::class, function () {
			return new View('');
		}, false);

		$context->registerService('isCLI', function () {
			return \OC::$CLI;
		});

		$context->registerMiddleware(ShareMiddleware::class);
		$context->registerMiddleware(APIMiddleware::class);

		$context->registerService('ShareController', function (ContainerInterface $c) {
			$server = $this->getContainer()->getServer();
			return new ShareController(
				$c->get('AppName'),
				$c->get('Request'),
				$server->getUserSession()->getUser(),
				$server->getGroupManager(),
				$server->getUserManager(),
				$c->get(ActivityService::class),
				$c->get(VaultService::class),
				$c->get(ShareService::class),
				$c->get(CredentialService::class),
				$c->get(NotificationService::class),
				$c->get(FileService::class),
				$c->get(SettingsService::class)
			);
		});


		$context->registerService('CronService', function (ContainerInterface $c) {
			return new CronService(
				$c->get(CredentialService::class),
				$c->get(ILogger::class),
				$c->get(Utils::class),
				$c->get(NotificationService::class),
				$c->get(ActivityService::class),
				$c->get(IDBConnection::class)
			);
		});

		$context->registerService('Logger', function (ContainerInterface $c) {
			return $c->get(ServerContainer::class)->getLogger();
		});
	}

	public function boot(IBootContext $context): void {
		$l = \OC::$server->getL10N(self::APP_ID);

		/** @var IManager $manager */
		$manager = $context->getAppContainer()->get(IManager::class);
		$manager->registerNotifierService(Notifier::class);

		Util::addTranslations(self::APP_ID);
		\OCP\App::registerAdmin(self::APP_ID, 'templates/admin.settings');
	}

	/**
	 * Register the navigation entry
	 */
	public function registerNavigationEntry() {
		$c = $this->getContainer();
		$server = $c->getServer();
		$navigationEntry = function () use ($c, $server) {
			return [
				'id' => $c->getAppName(),
				'order' => 10,
				'name' => $c->query(IL10N::class)->t('Passwords'),
				'href' => $server->getURLGenerator()->linkToRoute('passman.page.index'),
				'icon' => $server->getURLGenerator()->imagePath($c->getAppName(), 'app.svg'),
			];
		};
		$server->getNavigationManager()->add($navigationEntry);
	}

	/**
	 * Register personal settings for notifications and emails
	 */
	public function registerPersonalPage() {
		\OCP\App::registerPersonal($this->getContainer()->getAppName(), 'personal');
	}
}
