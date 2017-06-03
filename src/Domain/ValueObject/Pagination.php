<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject;

class Pagination implements \JsonSerializable
{
    private $currentPage;

    private $perPage;

    private $totalCount;

    private $totalPages;

    private $baseUrl;

    public function __construct(
        int $currentPage,
        int $perPage,
        int $totalCount,
        string $baseUrl = '/'
    ) {
        $this->currentPage = $currentPage;
        $this->totalCount = $totalCount;
        $this->perPage = $perPage;
        $this->baseUrl = $baseUrl;

        $this->totalPages = max(ceil($totalCount / $perPage), 1);
    }

    public function isOutOfBounds(): bool
    {
        return ($this->currentPage < 0 || $this->currentPage > $this->totalPages);
    }

    public function jsonSerialize(): array
    {
        return [
            'total' => $this->totalCount,
            'perPage' => $this->perPage,
            'currentPage' => $this->currentPage,
            'totalPages' => $this->totalPages,
            'previousPage' => $this->getPreviousPage(),
            'nextPage' => $this->getNextPage(),
            'previousPagePath' => $this->getPreviousPagePath(),
            'nextPagePath' => $this->getNextPagePath(),
        ];
    }

    private function getPreviousPage(): ?int
    {
        if ($this->currentPage > 1) {
            return $this->currentPage -1;
        }
        return null;
    }

    private function getNextPage(): ?int
    {
        if ($this->currentPage < $this->totalPages) {
            return $this->currentPage + 1;
        }
        return null;
    }

    private function getPreviousPagePath(): ?string
    {
        if (is_null($page = $this->getPreviousPage())) {
            return null;
        }
        return $this->getPath($page);
    }

    private function getNextPagePath(): ?string
    {
        if (is_null($page = $this->getNextPage())) {
            return null;
        }
        return $this->getPath($page);
    }

    private function getPath(int $page): string
    {
        if ($page === 1) {
            return $this->baseUrl;
        }
        return $this->baseUrl . '?' . http_build_query([
            'page' => $page
        ]);
    }
}
