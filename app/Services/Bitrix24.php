<?php
namespace App\Services;

class Bitrix24
{
    private const ENTITY = [
        "LEAD" => 1, // лид
        "DEAL" => 2, // сделка
        "CONTACT" => 3, // контакт
        "COMPANY" => 4, // компания
        "QUOTE" => 7, // предложение
        "INVOICE" =>   31, // счет
        "ETC" => 128, // смарт-процесс. Идентификатор конкретного смарт-процесса можно узнать методами crm.enum.ownertype и crm.type.list
    ];

    private $id;
    private $hash;
    private $domain;

    private static Bitrix24 $_instance;

    /**
     * Bitrix constructor.
     * @param string $domain
     * @param int $id
     * @param string $hash
     */
    public function __construct($domain, $id, $hash)
    {
        $this->domain = $domain;
        $this->id = $id;
        $this->hash = $hash;
    }

    public static function getInstance($domain, $id, $hash)
    {
        if (is_null(self::$_instance)) {
            return new static($domain, $id, $hash);
        }

        return self::$_instance;
    }

    /**
     * @param string $phone
     * @return string
     */
    public static function clearPhone($phone)
    {
        return preg_replace("/[^0-9]/", '', $phone);
    }

    /**
     * @param string $method
     * @param array $data
     * @return mixed
     */
    private function cUrl($method, $data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => "https://$this->domain/rest/$this->id/$this->hash/$method",
            CURLOPT_POSTFIELDS => $data,
        ]);
        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result);
    }

    /**
     * @param string $type
     * @param array $filter
     * @param array $select
     * @return stdClass[]|null
     */

    public function search($type, $filter, $select)
    {
        $method = "crm.$type.list";
        $queryData = http_build_query([
            'order' => ['ID' => 'DECS'],
            'filter' => $filter,
            'select' => $select
        ]);
        $res = $this->cUrl($method, $queryData);
        if ($res->total)
            return $res->result;
        return null;
    }

    /**
     * @param string $type
     * @param string $entityType
     * @param array $values
     * @return mixed
     */
    public function searchDuplicate($type, $entityType, array $values)
    {
        $method = "crm.duplicate.findbycomm";
        $queryData = http_build_query([
            'type' => $type,
            'values' => $values,
            'entity_type' => $entityType
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    /**
     * @param integer $responsibleId
     * @param string $title
     * @param string $name
     * @param string $phone
     * @param string $email
     * @param array $customFields
     * @param string $comment
     * @return mixed
     */
    public function addLead($fields)
    {
        $method = 'crm.lead.add';
        $lead = [
            'fields' => $fields,
            'params' => ['REGISTER_SONET_EVENT' => 'N']
        ];

        $res = $this->cUrl($method, http_build_query($lead));
        return $res->result;
    }

    /**
     * @param integer $responsibleId
     * @param string $title
     * @param integer $contactId
     * @param array $customFields
     * @param string $comment
     * @return int
     */
    public function addDeal($fields)
    {
        $method = 'crm.deal.add';
        $deal = [
            'fields' => $fields,
            'params' => ['REGISTER_SONET_EVENT' => 'Y']
        ];

        $res = $this->cUrl($method, http_build_query($deal));
        return $res->result;
    }

    /**
     * @param string $name
     * @param string $phone
     * @param string $email
     * @return int
     */
    public function addContact($name, $phone = null, $email = null)
    {
        $phone = self::clearPhone($phone);
        $method = 'crm.contact.add';
        $deal = http_build_query([
            'fields' => [
                'NAME' => $name,
                'PHONE' => [
                    [
                        'VALUE' => $phone
                    ]
                ],
                'EMAIL' => [
                    [
                        'VALUE' => $email
                    ]
                ],
            ],
            'params' => ['REGISTER_SONET_EVENT' => 'Y']
        ]);
        $res = $this->cUrl($method, $deal);
        return $res->result;
    }

    /**
     * @param $nameProduct string
     * @param $catalogId int
     * @return int|null
     */
    public function searchProduct($nameProduct, $catalogId = null)
    {
        $method = 'crm.product.list';
        $queryData = http_build_query([
            'order' => ['NAME' => 'ASC'],
            'filter' => [
                'CATALOG_ID' => $catalogId,
                'NAME' => $nameProduct
            ],
            'select' => ['ID', 'PRICE']
        ]);
        $res = $this->cUrl($method, $queryData);
        if ($res->total)
            return $res->result[0]->ID;
        return null;
    }

    /**
     * @param int $leadId
     * @param array $products
     * @return bool
     */
    public function addProductsToLead($leadId, $products)
    {
        $method = 'crm.lead.productrows.set';
        $queryData = http_build_query([
            'id' => $leadId,
            'rows' => $products
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    /**
     * @param int $dealId
     * @param array $products
     * @return bool
     */
    public function addProductsToDeal($dealId, $products)
    {
        $method = 'crm.deal.productrows.set';
        $queryData = http_build_query([
            'id' => $dealId,
            'rows' => $products
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    /**
     * @param int $leadId
     * @param array $fields
     * @return bool
     */
    public function updateLead($leadId, $fields)
    {
        $method = 'crm.lead.update';
        $queryData = http_build_query([
            'id' => $leadId,
            'fields' => $fields,
            'params' => [
                'REGISTER_SONET_EVENT' => 'Y'
            ],
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    /**
     * @param int $daelId
     * @param array $fields
     * @return bool
     */
    public function updateDeal($daelId, $fields)
    {
        $method = 'crm.deal.update';
        $queryData = http_build_query([
            'id' => $daelId,
            'fields' => $fields,
            'params' => [
                'REGISTER_SONET_EVENT' => 'Y'
            ],
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    /**
     * Список ордеров
     *
     * @param $type
     * @param $options
     * @return mixed
     */
    public function list($type, $options = [])
    {
        if (!$type) {
            throw new \RuntimeException('');
        }
        $method     = "crm.{$type}.list";
        $queryData  = array_merge([
            'filter' => [
                '>DATE_UPDATE' => (time() - (3600 * 24 * 7))
            ],
        ], $options);
        return $this->cUrl($method, http_build_query($queryData));
    }

    /**
     * @param $type
     * @param $options
     * @return mixed
     */
    public function clients($type, $options = [])
    {
        if (!$type) {
            throw new \RuntimeException('');
        }
        $method     = "crm.{$type}.list";
        $queryData  = array_merge([
            'order' => [
                'DATE_CREATE' => 'ASC'
            ],
        ], $options);
        return $this->cUrl($method, http_build_query($queryData));
    }

    /**
     * @param $leadId
     * @return stdClass|null
     */
    public function getLead($leadId)
    {
        $method = 'crm.lead.get';
        $queryData = http_build_query([
            'id' => $leadId
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    /**
     * @param array $filter
     * @param array $select
     * @return stdClass[]|null
     */
    public function getLeads($filter, $select, $start = 0)
    {
        $method = "crm.lead.list";
        $queryData = http_build_query([
            'order' => ['ID' => 'DESC'],
            'filter' => $filter,
            'select' => $select,
            'start'  => $start
        ]);
        return $this->cUrl($method, $queryData);
    }


    /**
     * @param $id
     * @return mixed
     */
    public function deleteLead($id)
    {
        $method = 'crm.lead.delete';
        $queryData = http_build_query([
            'id' => $id
        ]);
        return $this->cUrl($method, $queryData);
    }

    public function mergeLeads($ids)
    {
        $method = 'crm.lead.mergeBatch';
        $queryData = http_build_query([
            "params" => [
                "entityTypeId" => self::ENTITY["LEAD"],
                "entityIds" => $ids
            ]
        ]);
        return $this->cUrl($method, $queryData);
    }

    /**
     * @param $dealId
     * @return stdClass|null
     */
    public function getDeal($dealId)
    {
        $method = 'crm.deal.get';
        $queryData = http_build_query([
            'id' => $dealId
        ]);
        $res = $this->cUrl($method, $queryData);
        return !empty($res) ? $res->result : $res;
    }

    /**
     * @param array $filter
     * @param array $select
     * @return stdClass[]|null
     */

    public function getDeals($filter, $select, $start = 0)
    {
        $method = "crm.deal.list";
        $queryData = http_build_query([
            'order' => ['ID' => 'DESC'],
            'filter' => $filter,
            'select' => $select,
            'start'  => $start
        ]);
        return $this->cUrl($method, $queryData);
    }

    /**
     * @param $dealId
     * @return stdClass|null
     */
    public function getDealContacts($dealId)
    {
        $method = 'crm.deal.contact.items.get';
        $queryData = http_build_query([
            'id' => $dealId
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    /**
     * @param $contactId
     * @return stdClass|null
     */
    public function getContact($contactId)
    {
        $method = 'crm.contact.get';
        $queryData = http_build_query([
            'id' => $contactId
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }
    /**
     * @param array $filter
     * @param array $order
     * @param array $select
     */
    public function getContactList($filter = null, $select = null, $order = null)
    {
        $method = 'crm.contact.list';
        $queryData = http_build_query([
            'filter' => $filter,
            'select' => $select,
            'order'  => $order,
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    public function getOnlineUsers($id)
    {
        $method = 'user.get';
        $queryData = http_build_query([
            'FILTER' => [
                'ID' => $id,
                'IS_ONLINE' => 'Y',
            ],
        ]);

        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }


    public function getChat($id)
    {
        $method = 'im.dialog.messages.get';
        $queryData = http_build_query(array(
            'DIALOG_ID' => 'chat' . $id,
        ));
        $res = $this->cUrl($method, $queryData);
        return $res;
    }


    /**
     * @param int $leadId
     * @return array
     */
    public function getProductsToLead($leadId)
    {
        $method = 'crm.lead.productrows.get';
        $queryData = http_build_query(array(
            'id' => $leadId,
        ));
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    public function getProductsToDeal($dealId)
    {
        $method = 'crm.deal.productrows.get';
        $queryData = http_build_query(array(
            'id' => $dealId,
        ));
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    public function getCatalogProduct($productId)
    {
        $method = 'catalog.product.get';
        $queryData = http_build_query(array(
            'id' => $productId,
        ));
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    public function getCatalogProductList($start)
    {
        $method = "catalog.product.list";
        $queryData = http_build_query([
            'filter' => ["iblockId" => 15, ">property107" => 0],
            'select' => ["name", "id", "iblockId", "property107"],
            'start'  => $start
        ]);
        $res = $this->cUrl($method, $queryData);
        return $res->result;
    }

    public function getCatalogs()
    {
        $method = "catalog.catalog.list";
        $res = $this->cUrl($method, []);
        return $res->result;
    }

    /**
     * @param string $type
     * @param string $entityType
     * @param array $values
     * @return mixed
     */
    public function getCompany($id)
    {
        $method = "crm.company.get";
        $queryData = http_build_query(array(
            'id' => $id,
        ));
        return $this->cUrl($method, $queryData);
    }

    /**
     * @param string $type
     * @param string $entityType
     * @param array $values
     * @return mixed
     */
    public function getCompanyContacts($id)
    {
        $method = "crm.company.contact.items.get";
        $queryData = http_build_query(array(
            'id' => $id,
        ));
        return $this->cUrl($method, $queryData);
    }

    /**
     * @param string $type
     * @param string $entityType
     * @param array $values
     * @return mixed
     */
    public function sendMessage($id, $text, $phone)
    {
        $phone = self::clearPhone($phone);
        $method = 'crm.livefeedmessage.add';
        $fields = array(
            'POST_TITLE' => 'Повторное обращение',
            'MESSAGE' => $text,
            'ENTITYTYPEID' => 2,
            'ENTITYID' => $id,
        );

        $deal = http_build_query(array(
            'fields' => $fields
        ));
        $res = $this->cUrl($method, $deal);
        return $res->result;
    }

    /**
     * @param string $type
     * @param string $entityType
     * @param array $values
     * @return mixed
     */
    public function telephonyRegister($phone, $date)
    {
        date_default_timezone_set('Russia/Moscow');
        $date_now = date('Y-m-d H:i:s', time());

        $method = "telephony.externalcall.register";
        $queryData = http_build_query(array(
            'USER_ID' => 15,
            'PHONE_NUMBER' => $phone,
            'TYPE' => 2,
            'CALL_START_DATE' => $date_now,
            'CRM_CREATE' => false,
        ));
        return $this->cUrl($method, $queryData);
    }

    /**
     * @param string $type
     * @param string $entityType
     * @param array $values
     * @return mixed
     */
    public function telephonyFinish($id, $duration)
    {
        $method = "telephony.externalcall.finish";
        $queryData = http_build_query(array(
            'CALL_ID' => $id,
            'USER_ID' => 15,
            'DURATION' => $duration,
            'STATUS_CODE' => 200,
        ));
        return $this->cUrl($method, $queryData);
    }

    public function telephonyAttachRecord($id, $name, $url)
    {
        if (!empty($url)) {
            $method = "telephony.externalCall.attachRecord";
            $queryData = http_build_query(array(
                'CALL_ID' => $id,
                'FILENAME' => $name,
                'RECORD_URL' => $url,
            ));
            return $this->cUrl($method, $queryData);
        } else {
            return null;
        }
    }

    public function imopenlinesCrmChatGetLastId($id)
    {
        $method = "imopenlines.crm.chat.getLastId";
        $queryData = http_build_query(array(
            'CRM_ENTITY_TYPE'   => 'LEAD',
            'CRM_ENTITY'        => $id
        ));
        return $this->cUrl($method, $queryData);
    }

    public function imDialogMessagesGet($id)
    {
        $method = "im.dialog.messages.get";
        $queryData = http_build_query(array(
            'DIALOG_ID' => 'chat' . $id
        ));
        return $this->cUrl($method, $queryData);
    }

    /**
     * @param int $start
     * @return stdClass[]|null
     */
    public function getManagers($start = null)
    {
        $method = "user.get";
        $queryData = http_build_query(array(
            'filter' => array('USER_TYPE' => 'employee'),
            'select' => array(),
            'start' => $start
        ));
        return $this->cUrl($method, $queryData);
    }

    /**
     * @param $type
     * @return mixed
     */
    public function getFields($type)
    {
        $method = ($type !== 'order') ? "crm.{$type}.fields" : "sale.{$type}.getFields";
        return $this->cUrl($method, null);
    }

    public function getCategories($entityTypeId)
    {

        $method = "crm.category.list";
        $queryData = http_build_query([
            'entityTypeId' => $entityTypeId
        ]);

        return $this->cUrl($method, $queryData);
    }
}
