part of 'my_status_screen.dart';

class StatusMobileScreenLayout extends StatelessWidget {
  StatusMobileScreenLayout({super.key});
  final myStatusController = Get.put(MyStatusController());

  final userInfoController = Get.put(ProfileController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const PrimaryAppBar(Strings.myStatus, showBackButton: false),
      body: Obx(
        () => myStatusController.isLoading
            ? const CustomLoadingAPI()
            : Stack(
                children: [
                  _bodyWidget(context),
                  _customDraggableWidget(context),
                ],
              ),
      ),
      extendBody: false,
    );
  }

  Container _bodyWidget(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      decoration: BoxDecoration(
        boxShadow: [
          BoxShadow(
            color: CustomColor.blackColor.withValues(alpha: 0.07),
            spreadRadius: 0.5,
          ),
        ],
        color: CustomColor.whiteColor,
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(Dimensions.radius * 2),
          topLeft: Radius.circular(Dimensions.radius * 2),
        ),
      ),
      height: Dimensions.heightSize * 50,
      child: SingleChildScrollView(
        child: Column(
          children: [
            _personalInfoWidget(context),
            _overviewWidget(context),
            _inputWidget(context),
          ],
        ),
      ),
    );
  }

  Row _personalInfoWidget(context) {
    return Row(
      children: [
        CircleAvatar(
          radius: Dimensions.radius * 4,
          backgroundColor: CustomColor.whiteColor,
          child: DottedBorder(
            options: RoundedRectDottedBorderOptions(
              radius: Radius.circular(Dimensions.radius * 4),
              dashPattern: const [3, 1],

              color: CustomColor.primaryLightColor,
              strokeWidth: 2,
            ),

            child: userInfoController.imagePath.value.isNotEmpty
                ? ClipOval(
                    child: Image.file(
                      File(userInfoController.imagePath.value),
                      height: Dimensions.radius * 8,
                      width: Dimensions.radius * 8,
                      fit: BoxFit.cover,
                    ),
                  )
                : ClipOval(
                    child: Image.network(
                      userInfoController.userImage.value,
                      height: Dimensions.radius * 8,
                      width: Dimensions.radius * 8,
                      fit: BoxFit.cover,
                    ),
                  ),
          ),
        ),
        Padding(
          padding: EdgeInsets.only(left: Dimensions.paddingSize),
          child: Column(
            crossAxisAlignment: crossStart,
            children: [
              TitleHeading2Widget(
                text: userInfoController.userFullName.value,
                fontWeight: FontWeight.w800,
              ),
              Padding(
                padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.3),
                child: Container(
                  alignment: Alignment.center,
                  height: Dimensions.heightSize * 2.3,
                  width: Dimensions.widthSize * 5.8,
                  color: CustomColor.primaryLightColor.withValues(alpha: 0.1),
                  child: TitleHeading5Widget(
                    text: userInfoController.userLevel.value,
                    color: CustomColor.primaryLightColor,
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Obx _overviewWidget(context) {
    // final h = MediaQuery.of(context).size.height;
    // final w = MediaQuery.of(context).size.width;
    return Obx(
      () => Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize),
        child: Column(
          crossAxisAlignment: crossStart,
          children: [
            const TitleHeading3Widget(text: Strings.overview),
            verticalSpace(Dimensions.widthSize),
            Row(
              mainAxisAlignment: mainSpaceBet,
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: CustomColor.litePrimary,
                      borderRadius: BorderRadius.circular(
                        Dimensions.radius * 1.2,
                      ),
                    ),
                    width: Dimensions.widthSize * 15.5,
                    height: Dimensions.heightSize * 7.25,
                    child: Padding(
                      padding: EdgeInsets.only(
                        left: Dimensions.paddingSize * 0.5,
                        top: Dimensions.paddingSize * 0.5,
                      ),
                      child: Column(
                        crossAxisAlignment: crossStart,
                        children: [
                          const TitleHeading4Widget(
                            text: Strings.totalRefers,
                            color: CustomColor.liteBlack1,
                          ),
                          verticalSpace(Dimensions.paddingSize * 0.3),
                          TitleHeading2Widget(
                            text: "${myStatusController.totalRefers.value}",
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                horizontalSpace(Dimensions.widthSize),
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: CustomColor.litePrimary,
                      borderRadius: BorderRadius.circular(
                        Dimensions.radius * 1.2,
                      ),
                    ),
                    width: Dimensions.widthSize * 15.5,
                    height: Dimensions.heightSize * 7.25,
                    child: Padding(
                      padding: EdgeInsets.only(
                        left: Dimensions.paddingSize * 0.5,
                        top: Dimensions.paddingSize * 0.5,
                      ),
                      //.all(Dimensions.paddingSize),
                      child: Column(
                        crossAxisAlignment: crossStart,
                        children: [
                          const TitleHeading4Widget(
                            text: Strings.totalInvested,
                            color: CustomColor.liteBlack1,
                          ),
                          verticalSpace(Dimensions.paddingSize * 0.3),
                          TitleHeading2Widget(
                            text:
                                "${myStatusController.totalInvested.value} ${LocalStorage.baseCurrencyCode}",
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Padding _inputWidget(context) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSize),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          TitleHeading3Widget(
            text: Strings.referCode,
            color: CustomColor.blackColor.withValues(alpha: 0.7),
          ),
          PrimaryInputWidget(
            readOnly: true,
            textController: myStatusController.referCode,
            hintText: " ",
            prefixIconPath: Assets.icon.profile2userSvg,
            suffixIcon: Padding(
              padding: EdgeInsets.all(Dimensions.paddingSize * 0.4),
              child: GestureDetector(
                child: CustomImageWidget(path: Assets.icon.document),
                onTap: () async {
                  Clipboard.setData(
                    ClipboardData(text: myStatusController.referCode.text),
                  ).then((_) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(
                          DynamicLanguage.key(Strings.textCopy),
                          style: const TextStyle(color: Colors.red),
                        ),
                      ),
                    );
                  });
                },
              ),
            ),
          ),
          verticalSpace(Dimensions.paddingSize),
          TitleHeading3Widget(
            text: Strings.referLink,
            color: CustomColor.blackColor.withValues(alpha: 0.7),
          ),
          MediaQuery.of(context).viewInsets.bottom == 0
              ? PrimaryInputWidget(
                  textController: myStatusController.referLink,
                  readOnly: true,
                  hintText: " ",
                  prefixIconPath: Assets.icon.link,
                  suffixIcon: Padding(
                    padding: EdgeInsets.all(Dimensions.paddingSize * 0.4),
                    child: GestureDetector(
                      child: CustomImageWidget(path: Assets.icon.document),
                      onTap: () async {
                        Clipboard.setData(
                          ClipboardData(
                            text: myStatusController.referLink.text,
                          ),
                        ).then((_) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(
                                DynamicLanguage.key(Strings.textCopy),
                                style: const TextStyle(color: Colors.red),
                              ),
                            ),
                          );
                        });
                      },
                    ),
                  ),
                )
              : const SizedBox.shrink(),
        ],
      ),
    );
  }

  DraggableScrollableSheet _customDraggableWidget(context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.25,
      maxChildSize: 0.8,
      minChildSize: 0.2,
      builder: (BuildContext context, ScrollController scrollController) {
        return myStatusController.referralUsers.isEmpty
            ? const SizedBox.shrink()
            : ClipRRect(
                clipBehavior: Clip.none,
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(20.0),
                  topRight: Radius.circular(20.0),
                ),
                child: Container(
                  decoration: BoxDecoration(
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(20.0),
                      topRight: Radius.circular(20.0),
                    ),
                    color: CustomColor.whiteColor,
                    boxShadow: [
                      BoxShadow(
                        color: CustomColor.blackColor.withValues(alpha: 0.05),
                        spreadRadius: 12,
                        blurRadius: 25,
                        offset: const Offset(0, 0),
                      ),
                    ],
                  ),
                  child: Padding(
                    padding: EdgeInsets.only(
                      top: Dimensions.paddingSize,
                      left: Dimensions.paddingSize,
                      right: Dimensions.paddingSize,
                    ),
                    child: Column(
                      crossAxisAlignment: crossStart,
                      children: [
                        Padding(
                          padding: EdgeInsets.only(top: Dimensions.heightSize),
                          child: Container(
                            alignment: Alignment.center,
                            child: CustomImageWidget(
                              path: Assets.icon.slideBarRectangle,
                              width: Dimensions.widthSize * 4,
                              height: Dimensions.heightSize * 0.5,
                            ),
                          ),
                        ),
                        verticalSpace(Dimensions.heightSize),
                        const TitleHeading3Widget(text: Strings.referralUsers),
                        Expanded(
                          child: ListView.builder(
                            controller: scrollController,
                            padding: EdgeInsets.zero,
                            itemCount: myStatusController.referralUsers.length,
                            itemBuilder: (context, index) {
                              var users = myStatusController.referralUsers;
                              return Card(
                                elevation: 0.3,
                                child: ListTile(
                                  contentPadding: EdgeInsets.zero,
                                  leading: ClipRRect(
                                    borderRadius: BorderRadius.circular(
                                      Dimensions.radius * 10,
                                    ),
                                    child: Image.network(
                                      users[index].user.userImage,
                                      height: Dimensions.heightSize * 3.34,
                                      width: Dimensions.widthSize * 4,
                                    ),
                                  ),
                                  title: TitleHeading4Widget(
                                    text:
                                        "${users[index].user.firstname} ${users[index].user.lastname}",
                                  ),
                                  subtitle: TitleHeading4Widget(
                                    text: users[index].user.referralId,
                                    color: CustomColor.blackColor.withValues(
                                      alpha: 0.4,
                                    ),
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
      },
    );
  }
}
