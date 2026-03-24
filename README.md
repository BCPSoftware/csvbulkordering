# Oporteo_OrderUpload

Bulk add-to-cart module for Adobe Commerce 2.4.7 and PHP 8.3.

## What it does
- Adds a frontend page at `/orderupload`
- Accepts `csv`, `xls`, and `xlsx`
- Uses `csv` as the primary template format
- Runs a two-step flow:
  - validation on file selection
  - cart processing only after explicit confirmation
- Shows a validation summary before cart changes
- Skips invalid rows on proceed
- Uses consolidated customer messages after processing
- Logs process-time failures for rows that passed validation

## Extension model
Additional columns are extended through Magento observers/listeners.

### Collect extra template columns
Event:
- `oporteo_orderupload_collect_template_columns`

Observer input:
- `columns` as `Oporteo\OrderUpload\Model\Columns\Collection`
- register extra columns via `addColumn()`

### Prepare and validate extra row data
Event:
- `oporteo_orderupload_prepare_buy_request`

Observer input:
- `row`
- `product`
- `qty`
- `buy_request`
- `errors`

Observer responsibilities:
- read extra columns from `row`
- write mapped values into `buy_request`
- push customer-safe validation messages into `errors->getData('messages')`

### Validate uploaded headers
Event:
- `oporteo_orderupload_validate_headers`

Observer input:
- `headers` as `Oporteo\OrderUpload\Model\Columns\Collection`

Observer responsibilities:
- inspect normalized uploaded header names
- throw `LocalizedException` when required third-party columns are missing or invalid

## Example extension

`etc/frontend/events.xml`

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
    <event name="oporteo_orderupload_collect_template_columns">
        <observer name="vendor_orderupload_collect_columns"
                  instance="Vendor\Module\Observer\CollectOrderUploadColumns"/>
    </event>
    <event name="oporteo_orderupload_prepare_buy_request">
        <observer name="vendor_orderupload_prepare_buy_request"
                  instance="Vendor\Module\Observer\PrepareOrderUploadBuyRequest"/>
    </event>
    <event name="oporteo_orderupload_validate_headers">
        <observer name="vendor_orderupload_validate_headers"
                  instance="Vendor\Module\Observer\ValidateOrderUploadHeaders"/>
    </event>
</config>
```

`Observer/CollectOrderUploadColumns.php`

```php
<?php

declare(strict_types=1);

namespace Vendor\Module\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Oporteo\OrderUpload\Model\Columns\Collection;

class CollectOrderUploadColumns implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        /** @var Collection $columns */
        $columns = $observer->getData('columns');
        $columns->addColumn('comment');
    }
}
```

`Observer/ValidateOrderUploadHeaders.php`

```php
<?php

declare(strict_types=1);

namespace Vendor\Module\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Oporteo\OrderUpload\Model\Columns\Collection;

class ValidateOrderUploadHeaders implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        /** @var Collection $headers */
        $headers = $observer->getData('headers');

        if (!in_array('comment', $headers->toArray(), true)) {
            throw new LocalizedException(__('Comment column is required.'));
        }
    }
}
```

`Observer/PrepareOrderUploadBuyRequest.php`

```php
<?php

declare(strict_types=1);

namespace Vendor\Module\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class PrepareOrderUploadBuyRequest implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $row = (array)$observer->getData('row');

        /** @var DataObject $buyRequest */
        $buyRequest = $observer->getData('buy_request');

        /** @var DataObject $errors */
        $errors = $observer->getData('errors');

        $comment = trim((string)($row['comment'] ?? ''));
        if ($comment === '') {
            return;
        }

        if (mb_strlen($comment) > 255) {
            $messages = (array)$errors->getData('messages');
            $messages[] = 'Comment is too long.';
            $errors->setData('messages', $messages);

            return;
        }

        $buyRequest->setData('comment', $comment);
    }
}
```
