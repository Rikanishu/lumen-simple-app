<?php

namespace App\Listeners;

use App\Events\Event;
use App\Events\MoneyAdding;
use App\Events\MoneyTransfer;
use App\Helper\DateTimeFormatHelper;
use App\Models\BalanceEvent;

class ReportEventsListener
{
    /**
     * Handle the event.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle(Event $event)
    {
        $eventDate = new \DateTime();

        if ($event instanceof MoneyAdding) {

            $eventEntry = new BalanceEvent();
            $eventEntry->client_id = $event->getClient()->id;
            $eventEntry->event_type = BalanceEvent::TYPE_ADDING;
            $eventEntry->date = DateTimeFormatHelper::formatToDbDateTime($eventDate);
            $eventEntry->amount = $event->getAmount();
            $eventEntry->save();

        } else if ($event instanceof MoneyTransfer) {

            $eventEntry = new BalanceEvent();
            $eventEntry->client_id = $event->getSender()->id;
            $eventEntry->event_type = BalanceEvent::TYPE_TRANSFER_SUBTRACT;
            $eventEntry->participant_client_id = $event->getReceiver()->id;
            $eventEntry->date = DateTimeFormatHelper::formatToDbDateTime($eventDate);
            $eventEntry->amount = $event->getAmountSubtracted();
            $eventEntry->save();

            $eventEntry = new BalanceEvent();
            $eventEntry->client_id = $event->getReceiver()->id;
            $eventEntry->participant_client_id = $event->getSender()->id;
            $eventEntry->event_type = BalanceEvent::TYPE_TRANSFER_ADDING;
            $eventEntry->date = DateTimeFormatHelper::formatToDbDateTime($eventDate);
            $eventEntry->amount = $event->getAmountAdded();
            $eventEntry->save();

        }
    }
}
