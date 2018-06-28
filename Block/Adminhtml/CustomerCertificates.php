<?php
/**
 * @category    ClassyLlama
 * @copyright   Copyright (c) 2018 Classy Llama Studios, LLC
 * @author      sean.templeton
 */

namespace ClassyLlama\AvaTax\Block\Adminhtml;

use ClassyLlama\AvaTax\Exception\AvataxConnectionException;
use ClassyLlama\AvaTax\Framework\Interaction\Rest\Customer;
use ClassyLlama\AvaTax\Helper\UrlSigner;
use Magento\Backend\Block\Template;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;

/**
 * @method setCertificates(DataObject[] $certificates)
 * @method DataObject[] getCertificates()
 */
class CustomerCertificates extends Template implements \Magento\Ui\Component\Layout\Tabs\TabInterface
{
    protected $_template = 'ClassyLlama_AvaTax::customer-certificates.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var Customer
     */
    protected $customerRest;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var UrlSigner
     */
    protected $urlSigner;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param Template\Context            $context
     * @param Customer                    $customerRest
     * @param DataObjectFactory           $dataObjectFactory
     * @param UrlSigner                   $urlSigner
     * @param array                       $data
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        Template\Context $context,
        Customer $customerRest,
        DataObjectFactory $dataObjectFactory,
        UrlSigner $urlSigner,
        array $data = []
    )
    {
        parent::__construct( $context, $data );

        $this->coreRegistry = $coreRegistry;
        $this->customerRest = $customerRest;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->urlSigner = $urlSigner;

        $this->prepareData();
    }

    protected function prepareData()
    {
        $certificates = [];

        try
        {
            $certificates = $this->customerRest->getCertificatesList(
                $this->dataObjectFactory->create(
                    [
                        'data' => [
                            'customer_id' => $this->coreRegistry->registry(
                                RegistryConstants::CURRENT_CUSTOMER_ID
                            )
                        ]
                    ]
                )
            );
        }
        catch (AvataxConnectionException $e)
        {
        }

        $this->setCertificates( $certificates );
    }

    /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __( 'Tax Certificates' );
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __( 'Tax Certificates' );
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return $this->coreRegistry->registry( RegistryConstants::CURRENT_CUSTOMER_ID ) !== null;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    public function getCertificateUrl( $certificateId )
    {
        $parameters = [
            'certificate_id' => $certificateId,
            'customer_id'    => $this->coreRegistry->registry(
                RegistryConstants::CURRENT_CUSTOMER_ID
            ),
            'expires'        => time() + (60 * 60 * 24) // 24 hour access
        ];

        $parameters['signature'] = $this->urlSigner->signParameters($parameters);
        // This messes with URL signing as the parameter is added after the fact. Don't use url keys for certificate downloads
        $parameters['_nosecret'] = true;

        return $this->getUrl('avatax/certificates/download', $parameters);
    }
}