<?php

namespace App\Service;

use InvalidArgumentException;
use App\Repository\LinkBridgeRepository;

class LinkBridgeService
{
	public string $default_qr_vendor = "https://api.qrserver.com/v1/create-qr-code/?margin=20&size=300x300&data=";

	public function __construct(public readonly LinkBridgeRepository $LinkBridgeRepo)
	{
	}

	/**
	 * Метод для генерации цифрового PIN-Кода.
	 *
	 * @param int $length
	 * @return string
	 */
	public function getPinCode(int $length = 6): string
	{
		if ($length < 1) throw new InvalidArgumentException("PIN length is invalid");
		$result = "";
		for ($i = 0; $i < $length; $i++) $result .= rand(0, 9);
		return $result;
	}

	/**
	 * Метод обрезает все символы не являющиеся частью цифрового PIN-Кода.
	 *
	 * @param string|int|null $pin
	 * @return string
	 */
	public function sanitizePinCode(string|int|null $pin): string
	{
		return preg_replace(pattern: "/\D+/", replacement: "", subject: $pin);
	}

	/**
	 * Метод обрабатывает ссылку добавляя протокол при его отсутствии.
	 *
	 * @param string|null $link
	 * @return string
	 * @noinspection PhpUnused
	 * @noinspection HttpUrlsUsage
	 */
	public function sanitizeUrl(string|null $link): string
	{
		if (empty($link)) throw new InvalidArgumentException("URL cannot be empty");
		return str_contains($link, "://") ? $link : "http://" . $link;
	}

	/**
	 * Метод делит код на указанные части с указанным разделителем.
	 *
	 * @param string $code
	 * @param int $groupSize
	 * @param string $separator
	 * @return string
	 */
	public function splitCode(string $code, int $groupSize = 3, string $separator = "-"): string
	{
		$result = [];
		for ($i = 0; $i < strlen($code); $i += $groupSize) $result[] = substr($code, $i, $groupSize);
		return implode($separator, $result);
	}

	/**
	 * Метод возвращает ссылку на QR-Код.
	 *
	 * @param string $data
	 * @param int $timeout
	 * @return string|false
	 */
	public function getQRCode(string $data = "", int $timeout = 10): string|false
	{
		$vendor = !empty($_ENV["THIS_PROJECT_QR_VENDOR"]) ? $_ENV["THIS_PROJECT_QR_VENDOR"] : $this->default_qr_vendor;
		$url = $vendor . urlencode($data);
		$QR_image = @fopen($url, "rb", context: stream_context_create(["http" => ["timeout" => $timeout]]));
		return $QR_image ? $url : false;
	}

}
