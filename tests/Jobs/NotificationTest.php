<?php

namespace tests\Jobs;

use AbuseIO\Jobs\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Log;
use tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    private $notification;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->notification = new Notification();
    }

    /** @test */
    public function there_is_no_notifications_key_in_config()
    {
        unset(app()['config']['notifications']);

        Log::shouldReceive('debug')
            ->once()
            ->with('AbuseIO\Jobs\Notification: No notification methods are installed, skipping notifications');

        $this->assertFalse($this->notification->send([]));
    }

    /** @test */
    public function the_notification_mailer_is_disabled_it_should_return_string_disabled()
    {
        config(['notifications.Mail.notification.enabled' => false]);

        $this->assertEquals(
            'disabled',
            $this->notification->send([])
        );
    }

    /** @test */
    public function the_notification_factory_could_not_create_notification_object()
    {
        $dummyConfig = ['MailerXL' => ['notification' => ['enabled' => true]]];

        config(['notifications' => $dummyConfig]);

        Log::shouldReceive('error')
            ->never();

        Log::shouldReceive('debug')
            ->never();

        Log::shouldReceive('info')
            ->once()
            ->with('AbuseIO\Notification\Factory: The notification MailerXL is not present on this system');

        $this->assertFalse($this->notification->send([]));
    }

    /**
     * @test
     */
    public function the_notification_was_send_but_its_result_has_errorStatus_it_should_return_false()
    {
        $stub = $this->getMock('AbuseIO\Notification\Mail', ['send']);
        $stub->method('send')
            ->will($this->returnValue([
                'errorStatus' => true
            ]));

        $notification = $this->getMock('AbuseIO\Jobs\Notification', ['getNotificationInstance']);

        $notification->expects($this->once())
            ->method('getNotificationInstance')
            ->will($this->returnValue(
                $stub
            ));


        $this->assertFalse($notification->send([]));
    }
    /** @test */
    public function the_notification_was_send_and_the_notification_was_debug_logged_and_returns_true()
    {
        $stub = $this->getMock('AbuseIO\Notification\Mail', ['send']);
        $stub->method('send')
            ->will($this->returnValue([
                'errorStatus' => false
            ]));

        $notification = $this->getMock('AbuseIO\Jobs\Notification', ['getNotificationInstance']);

        $notification->expects($this->once())
            ->method('getNotificationInstance')
            ->will($this->returnValue(
                $stub
            ));

        Log::shouldReceive('debug')
            ->once();

        $this->assertTrue($notification->send([]));
    }
}