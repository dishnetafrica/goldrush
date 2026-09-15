part of 'notification_screen.dart';

class NotificationMobileScreenLayout extends StatelessWidget {
  NotificationMobileScreenLayout({super.key});
  final NotificationController notificationController =
      Get.put(NotificationController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
        appBar: const PrimaryAppBar(
          showBackButton: false,
          Strings.notifications,
        ),
        body: Obx(
          () => notificationController.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ));
  }

  Column _bodyWidget(BuildContext context) {
    return Column(
      children: [_allNotificationWidget(context)],
    );
  }

  Expanded _allNotificationWidget(BuildContext context) {
    final w = MediaQuery.of(context).size.width;
    final dateOnly = DateFormat('dd');
    final monthOnly = DateFormat('MMM');
    return Expanded(
        child: Container(
      decoration: BoxDecoration(
        color: CustomColor.whiteColor,
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(Dimensions.radius * 2),
          topLeft: Radius.circular(Dimensions.radius * 2),
        ),
      ),
      child: Obx(
        () => notificationController.isLoading
            ? const CustomLoadingAPI()
            : notificationController.notifications!.isEmpty
                ? Center(
                    child: Text(
                      DynamicLanguage.key(Strings.noNotificationAvailable),
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: Dimensions.headingTextSize4,
                      ),
                    ),
                  )
                : ListView.builder(
                    itemCount: notificationController.notifications!.length,
                    padding: EdgeInsets.zero,
                    itemBuilder: (context, index) {
                      var notifications = notificationController.notifications;
                      return Padding(
                        padding: EdgeInsets.only(
                            top: Dimensions.paddingSize * 0.1,
                            left: Dimensions.paddingSize * 0.5,
                            right: Dimensions.paddingSize * 0.5),
                        child: Column(
                          children: [
                            Row(
                              children: [
                                DateInfoWidget(
                                  dateText: dateOnly
                                      .format(notifications![index].createdAt),
                                  monthText: monthOnly
                                      .format(notifications[index].createdAt),
                                ),
                                Padding(
                                  padding: EdgeInsets.all(
                                      Dimensions.paddingSize * 0.5),
                                  child: Column(
                                    crossAxisAlignment: crossStart,
                                    children: [
                                      TitleHeading4Widget(
                                        text:
                                            notifications[index].message.title,
                                        fontWeight: FontWeight.w700,
                                      ),
                                      SizedBox(
                                        width: w * 0.75,
                                        child: TitleHeading4Widget(
                                          text: notifications[index]
                                              .message
                                              .message,
                                          maxLines: 1,
                                          textOverflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                )
                              ],
                            ),
                            Divider(
                              height: Dimensions.heightSize,
                              color: CustomColor.blackColor.withValues(alpha: 0.05),
                              thickness: 1,
                            ),
                          ],
                        ),
                      );
                    },
                  ),
      ),
    ));
  }
}
