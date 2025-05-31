<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use Exception;
use DateTimeImmutable;
use App\Entity\LinkBridge;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<LinkBridge>
 */
class LinkBridgeRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, LinkBridge::class);
	}

	public function getEntityManagerInterface(): EntityManagerInterface
	{
		return $this->getEntityManager();
	}

	/**
	 * Метод удаляет запись полученной ссылки.
	 *
	 * @param LinkBridge $item
	 * @return void
	 */
	public function removeReceivedLink(LinkBridge $item): void
	{
		$em = $this->getEntityManager();
		$em->remove($item);
		$em->flush();
	}

	/**
	 * Метод очищает БД от неактивных сессий.
	 *
	 * @param string $modifier
	 * @return int Количество затронутых строк
	 */
	public function cleanupOldSessions(string $modifier = "-5 minutes"): int
	{
		$cutoff = (new DateTimeImmutable())->modify($modifier);
		$em = $this->getEntityManager();

		return $em->createQueryBuilder()
			->delete(LinkBridge::class, "item")
			->where("item.registeredAt <= :cutoff")
			->setParameter("cutoff", $cutoff)
			->getQuery()
			->execute();
	}

	/**
	 * Метод обновляет время последнего обнаружения PIN-Кода.
	 *
	 * @param LinkBridge $item
	 * @return string|null
	 */
	public function updatePinRequest(LinkBridge $item): string|null
	{
		$item->setRegisteredAt(new DateTimeImmutable());
		$this->getEntityManager()->flush();
		return $item->getPin();
	}

	/**
	 * Метод регистрирует нового получателя.
	 *
	 * @param string $ssid
	 * @param string|int $pin
	 * @return LinkBridge
	 */
	public function registerNewRecipient(string $ssid, string|int $pin): LinkBridge
	{
		$new = new LinkBridge();
		$new->setSessionId($ssid)
			->setPin($pin)
			->setRegisteredAt(new DateTimeImmutable());

		$em = $this->getEntityManager();
		$em->persist($new);
		$em->flush();

		return $new;
	}

	/**
	 * Метод записывает ссылку в адрес получателя.
	 *
	 * @param string|int $pin
	 * @param string $url
	 * @return LinkBridge
	 * @throws Exception
	 */
	public function writeLinkForRecipient(string|int $pin, string $url): LinkBridge
	{
		$recipient = $this->findOneBy(["pin" => $pin]);
		if (!$recipient instanceof LinkBridge) throw new Exception("The recipient's device was not found.");

		$em = $this->getEntityManager();
		$recipient->setUrl($url);
		$recipient->setRegisteredAt(new DateTimeImmutable());
		$em->persist($recipient);
		$em->flush();
		return $recipient;
	}

	/**
	 * Метод возвращает список PIN-Кодов получателей.
	 *
	 * @return array
	 */
	public function getAllRecipientList(): array
	{
		$recipients = $this->findAll();
		$result = [];

		foreach ($recipients as $recipient) {
			$result[] = [
				"id" => $recipient->getId(),
				"pin" => $recipient->getPin(),
				"timestamp" => $recipient->getRegisteredAt()->getTimestamp(),
			];
		}

		return $result;
	}

}
