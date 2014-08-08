<?php 
namespace RZ\Renzo\Core\ListManagers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManager;
/**
 * Perform basic filtering and search over entity listings.
 * 
 */
class EntityListManager
{
	const ITEM_PER_PAGE = 15;

	protected $request = null;
	protected $_em = null;
	protected $entityName;
	protected $paginator = null;

	protected $orderingArray = null;
	protected $filteringArray = null;
	protected $searchPattern = null;
	protected $currentPage = null;

	protected $assignation = null;

	/**
	 * 
	 * @param Symfony\Component\HttpFoundation\Request $request
	 * @param Doctrine\ORM\EntityManager $_em 
	 * @param string $entityName
	 * @param array $preFilters Initial filters
	 * @param array $preOrdering Initial order
	 */
	function __construct( Request $request, EntityManager $_em, $entityName, $preFilters = array(), $preOrdering = array() )
	{
		$this->request =    $request;
		$this->entityName = $entityName;
		$this->_em =        $_em;

		$this->orderingArray = $preFilters;
		$this->filteringArray = $preOrdering;
		$this->assignation = array();
	}

	/**
	 * Handle request to find filter to apply to entity listing.
	 * @return void
	 */
	public function handle()
	{
		if ($this->request->query->get('field') && 
			$this->request->query->get('ordering')) {

			$this->orderingArray[$this->request->query->get('field')] = $this->request->query->get('ordering');
		}

		if ($this->request->query->get('search') != "") {
			$this->searchPattern = $this->request->query->get('search');
		}

		$this->currentPage = $this->request->query->get('page');
		if (!($this->currentPage > 1)) {
			$this->currentPage = 1;
		}

		$this->paginator = new \RZ\Renzo\Core\Utils\Paginator( 
			$this->_em, 
			$this->entityName, 
			static::ITEM_PER_PAGE,
			$this->filteringArray
		);
	}

	/**
	 * Get Twig assignation to render list details.
	 * 
	 * ## Fields:
	 * 
	 * * description
	 * * search
	 * * currentPage
	 * * pageCount
	 * * itemPerPage
	 * * itemCount 
	 * 
	 * @return array
	 */
	public function getAssignation()
	{
		try {
			return array(
				'description' => '',
				'search'      => $this->searchPattern,
				'currentPage' => $this->currentPage,
				'pageCount'   => $this->paginator->getPageCount(),
				'itemPerPage' => static::ITEM_PER_PAGE,
				'itemCount'   => $this->_em->getRepository($this->entityName)->countBy($this->filteringArray)
			);
		}
		catch(\Exception $e){
			return null;
		}
	}

	/**
	 * Return filtered entities.
	 * 
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getEntities()
	{
		try {
			if ($this->searchPattern != '') {
				return $this->_em
					->getRepository($this->entityName)
					->searchBy($this->searchPattern, $this->filteringArray, $this->orderingArray);
			}
			else {
				return $this->paginator->findByAtPage($this->filteringArray, $this->currentPage);
			}
		}
		catch(\Exception $e){
			return null;
		}
	}
}