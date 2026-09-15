part of 'dashboard_screen.dart';

class DashboardMobileScreenLayout extends StatelessWidget {
  DashboardMobileScreenLayout({super.key});
  final GlobalKey<ScaffoldState> _key = GlobalKey();
  final ProfileController userInfoController = Get.put(ProfileController());
  final controller = Get.put(LogsController());
  final dateOnly = DateFormat('dd');
  final monthOnly = DateFormat('MMM');

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      drawer: DrawerScreen(),
      key: _key,
      body: Obx(
        () => userInfoController.isLoading
            ? const CustomLoadingAPI()
            : Stack(
                children: [
                  _bodyWidget(context),
                  _customDraggableWidget(context),
                ],
              ),
      ),
      extendBody: true,
    );
  }

  Padding _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: Dimensions.paddingSize,
        right: Dimensions.paddingSize,
      ),
      child: Column(
        children: [
          _topDesignWIdget(context),
          _currentBalanceWidget(context),
          _transactionTrayWidget(context),
        ],
      ),
    );
  }

  DashboardTopClipPath _currentBalanceWidget(context) {
    return DashboardTopClipPath();
  }

  Padding _topDesignWIdget(context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.heightSize * 3),
      child: Row(
        mainAxisAlignment: mainSpaceBet,
        children: [
          IconButton(
            padding: EdgeInsets.zero,
            onPressed: () {
              Scaffold.of(context).openDrawer();
            },
            icon: CustomImageWidget(
              path: Assets.icon.menuButton,
              height: Dimensions.heightSize * 3,
              width: Dimensions.widthSize * 2,
            ),
          ),
          Padding(
            padding: EdgeInsets.only(right: Dimensions.paddingSize * 0.2),
            child: Row(
              children: [
                Container(
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: CustomColor.primaryLightColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(
                      Dimensions.radius * 0.8,
                    ),
                  ),
                  child: Padding(
                    padding: EdgeInsets.symmetric(
                      horizontal: Dimensions.paddingSize * 0.3,
                      vertical: Dimensions.paddingSize * 0.2,
                    ),
                    child: TitleHeading5Widget(
                      text: "${userInfoController.userLevel}",
                      color: CustomColor.primaryLightColor,
                    ),
                  ),
                ),
                Padding(
                  padding: EdgeInsets.only(left: Dimensions.paddingSize * 0.3),
                  child: GestureDetector(
                    onTap: () => Get.toNamed(Routes.profile),
                    child: DottedBorder(
                      options: RoundedRectDottedBorderOptions(
                        radius: Radius.circular(Dimensions.radius * 2),
                        dashPattern: const [3, 3],
               
                        color: CustomColor.primaryLightColor,
                        strokeWidth: 2,
                      ),

                
                      child: userInfoController.imagePath.value != ''
                          ? ClipOval(
                              child: Image.file(
                                File(userInfoController.imagePath.value),
                                height: Dimensions.radius * 4,
                                width: Dimensions.radius * 4,
                                fit: BoxFit.cover,
                              ),
                            )
                          : ClipOval(
                              child: Image.network(
                                userInfoController.userImage.value,
                                height: Dimensions.radius * 4,
                                width: Dimensions.radius * 4,
                                fit: BoxFit.cover,
                              ),
                            ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Padding _transactionTrayWidget(context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          CustomIconWithLabelWidget(
            onTap: () => Get.offAllNamed(Routes.addMoneyScreen),
            imagePath: Assets.icon.addMoney,
            labelText: Strings.addMoney,
          ),
          CustomIconWithLabelWidget(
            onTap: () => Get.offAllNamed(Routes.moneyOutScreen),
            imagePath: Assets.icon.moneyOut,
            labelText: Strings.moneyOut,
          ),
          CustomIconWithLabelWidget(
            onTap: () => Get.offAllNamed(Routes.sendMoneyScreen),
            imagePath: Assets.icon.sendMoney,
            labelText: Strings.sendMoney,
          ),
        ],
      ),
    );
  }

  DraggableScrollableSheet _customDraggableWidget(context) {
    final w = MediaQuery.of(context).size.width;
    return DraggableScrollableSheet(
      initialChildSize: w > 1 ? 0.35 : 0.4,
      maxChildSize: 0.8,
      minChildSize: 0.2,
      builder: (BuildContext context, ScrollController scrollController) {
        return ClipRRect(
          clipBehavior: Clip.none,
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
              padding: EdgeInsets.all(Dimensions.paddingSize * 0.5),
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
                  Padding(
                    padding: EdgeInsets.only(
                      left: Dimensions.paddingSize * 0.6,
                    ),
                    child: TitleHeading3Widget(
                      text: Strings.latestTransactions,
                      fontSize: Dimensions.headingTextSize2 * 0.9,
                    ),
                  ),
                  Expanded(
                    child: controller.transactions.isEmpty
                        ? Center(
                            child: Text(
                              DynamicLanguage.key(
                                Strings.noTransactionsAvailable,
                              ),
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: Dimensions.headingTextSize4,
                              ),
                            ),
                          )
                        : ListView.builder(
                            controller: scrollController,
                            itemCount: controller.transactions.length,
                            itemBuilder: (context, index) {
                              var data = controller.transactions[index];
                              return Padding(
                                padding: EdgeInsets.only(
                                  left: Dimensions.paddingSize * 0.5,
                                  right: Dimensions.paddingSize * 0.5,
                                ),
                                child: CustomWalletCard(
                                  fontWeightTitle: FontWeight.w700,
                                  walletTitle: data.type,
                                  fontSizeTitle: Dimensions.headingTextSize4,
                                  walletSubTitle: data.trxId,
                                  fontWeightSubTitleMain: FontWeight.w400,
                                  fontSizeSubtitle: Dimensions.headingTextSize4,
                                  amount:
                                      "${data.receiveAmount.toStringAsFixed(2)} ${data.requestCurrency}",
                                  customWidget: DateInfoWidget(
                                    dateText: dateOnly.format(data.createdAt),
                                    monthText: monthOnly.format(data.createdAt),
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
