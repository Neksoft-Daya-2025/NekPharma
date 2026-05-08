<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\Tour;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class TourPlanSubmitted extends BaseNotification
{
    private $tour;
    private $emailSetting;

    public function __construct(Tour $tour)
    {
        $this->tour = $tour;
        $this->company = $tour->company;
        if ($this->company) {
            $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'tour-plan-submitted')->first();
        }
    }

    public function via($notifiable)
    {
        $via = ['database'];

        if (is_null($this->company)) {
            array_push($via, 'mail');
            return $via;
        }

        if ($this->emailSetting && $notifiable) {
            if ($this->emailSetting->send_email == 'yes' && $notifiable->email_notifications && $notifiable->email != '') {
                array_push($via, 'mail');
            }
            if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting && $this->company->slackSetting->status == 'active') {
                $this->slackUserNameCheck($notifiable) ? array_push($via, 'slack') : null;
            }
            if ($this->emailSetting->send_push == 'yes' && push_setting()->status == 'active') {
                array_push($via, OneSignalChannel::class);
            }
        }

        return $via;
    }

    public function toMail($notifiable)
    {
        $build = parent::build($notifiable);
        $url = route('tours.index');
        $url = getDomainSpecificUrl($url, $this->company);
        $submitterName = $this->tour->user ? $this->tour->user->name : __('Employee');
        $content = $submitterName . ' ' . __('has submitted a tour plan for approval for date') . ' ' . $this->tour->date->format(company()->date_format) . '.';

        $build
            ->subject(__('Tour plan submitted for approval') . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('View tour plan'),
                'notifiableName' => $notifiable->name
            ]);

        parent::resetLocale();
        return $build;
    }

    public function toArray($notifiable)
    {
        return [
            'id' => $this->tour->id,
            'user_id' => $notifiable->id,
            'tour_date' => $this->tour->date?->format('Y-m-d'),
            'submitted_by' => $this->tour->user_id,
            'message' => __('Tour plan submitted for approval for') . ' ' . $this->tour->date?->format(company()->date_format),
        ];
    }

    public function toSlack($notifiable)
    {
        if ($this->slackUserNameCheck($notifiable)) {
            return $this->slackBuild($notifiable)
                ->content(__('Tour plan submitted for approval for') . ' ' . $this->tour->date?->format(company()->date_format));
        }
        return $this->slackRedirectMessage('Tour plan submitted for approval', $notifiable);
    }

    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->setSubject(__('Tour plan submitted for approval'))
            ->setBody(__('Tour plan submitted for approval for') . ' ' . $this->tour->date?->format(company()->date_format));
    }
}
