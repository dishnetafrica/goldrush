part of 'history_screen.dart';

// ignore: must_be_immutable
class HistoryMobileScreenLayout extends StatelessWidget {
  int? selectedPage;
  HistoryMobileScreenLayout({super.key, this.selectedPage});
  final dateOnly = DateFormat('dd');
  final monthOnly = DateFormat('MMM');

  final controller = Get.put(LogsController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: PrimaryAppBar(
        backgroundColor: CustomColor.blackColor.withValues(alpha: 0.05),
        Strings.history,
        showBackButton: false,
      ),
      body: Column(
        children: [
          Expanded(child: _bodyWidget(context)),
        ],
      ),
    );
  }

  dynamic _bodyWidget(BuildContext context) {
    return _tabBarWithView(context);
  }

  Container _tabBarWithView(context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(Dimensions.radius * 0.8),
      ),
      child: DefaultTabController(
        initialIndex: selectedPage!,
        length: 3,
        child: Column(
          children: [
            Container(
              padding: EdgeInsets.only(
                left: Dimensions.paddingSize * 1.1,
                right: Dimensions.paddingSize * 1.1,
              ),
              color: CustomColor.blackColor.withValues(alpha: 0.05),
              height: Dimensions.heightSize * 3.5,
              width: double.infinity,
              child: TabBar(
                splashFactory: NoSplash.splashFactory,
                overlayColor: WidgetStateProperty.resolveWith<Color?>((states) {
                  if (states.contains(WidgetState.pressed)) {
                    return Colors.red.withValues(alpha: 0.2);
                  }
                  return null;
                }),
                dividerColor: Colors.transparent,
                indicatorPadding: EdgeInsets.zero,
                indicatorColor: Colors.transparent,
                indicatorSize: TabBarIndicatorSize.label,
                indicator: BoxDecoration(
                  color: CustomColor.primaryLightColor,
                  borderRadius: BorderRadius.circular(Dimensions.radius * 0.8),
                ),
                unselectedLabelColor: CustomColor.blackColor.withValues(alpha: 0.5),
                labelColor: CustomColor.whiteColor,
                labelStyle: const TextStyle(fontWeight: FontWeight.w500),
                labelPadding: const EdgeInsets.symmetric(
                    horizontal: 6.0, vertical: 0.00001),
                tabs: const [
                  Tab(child: CustomTabWidget(title: Strings.profitLog)),
                  Tab(child: CustomTabWidget(title: Strings.transaction)),
                  Tab(child: CustomTabWidget(title: Strings.orderLog)),
                ],
              ),
            ),
            SizedBox(
              height: MediaQuery.of(context).size.height * 0.7,
              child: TabBarView(
                children: [
                  _profitWidget(context),
                  _transactionWidget(context),
                  _orderLogWidget(context)
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Container _profitWidget(context) {
    return Container(
      color: CustomColor.blackColor.withValues(alpha: 0.05),
      child: Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize * 1.5),
        child: Container(
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
          child: controller.profits.isEmpty
              ? RefreshIndicator(
                  color: CustomColor.whiteColor,
                  backgroundColor: CustomColor.primaryLightColor,
                  strokeWidth: 2.5,
                  onRefresh: () async {
                    controller.profitInfoProcess();
                    return Future<void>.delayed(const Duration(seconds: 3));
                  },
                  child: ListView(
                    children: [
                      SizedBox(
                        height: MediaQuery.of(context).size.height * 0.6,
                        child: Center(
                          child: Text(
                            DynamicLanguage.key(Strings.noProfitAvailable),
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: Dimensions.headingTextSize4,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  color: CustomColor.whiteColor,
                  backgroundColor: CustomColor.primaryLightColor,
                  strokeWidth: 2.5,
                  onRefresh: () async {
                    controller.profitInfoProcess();
                    return Future<void>.delayed(const Duration(seconds: 3));
                  },
                  child: ListView.builder(
                    itemCount: controller.profits.length,
                    padding: EdgeInsets.zero,
                    itemBuilder: (context, index) {
                      var profits = controller.profits[index];

                      return Padding(
                        padding: EdgeInsets.only(
                            left: Dimensions.paddingSize,
                            right: Dimensions.paddingSize),
                        child: CustomWalletCard(
                          fontWeightTitle: FontWeight.w700,
                          fontColorTitle: CustomColor.primaryLightTextColor,
                          walletTitle: profits.title,
                          walletSubTitle: '${profits.duration.toString()} Days',
                          fontSizeTitle: Dimensions.headingTextSize4,
                          fontWeightSubTitleMain: FontWeight.w400,
                          fontSizeSubtitle: Dimensions.headingTextSize4,
                          amount:
                              '${profits.profitAmount.toStringAsFixed(2)} ${LocalStorage.baseCurrencyCode}',
                          balanceText:
                              '${profits.investmentAmount.toStringAsFixed(2)} ${LocalStorage.baseCurrencyCode}',
                          customWidget: DateInfoWidget(
                            dateText: dateOnly.format(profits.createdAt),
                            monthText: monthOnly.format(profits.createdAt),
                          ),
                        ),
                      );
                    },
                  ),
                ),
        ),
      ),
    );
  }

  Container _transactionWidget(context) {
    return Container(
      color: CustomColor.blackColor.withValues(alpha: 0.05),
      child: Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize * 1.5),
        child: Container(
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
          child: controller.transactions.isEmpty
              ? RefreshIndicator(
                  color: CustomColor.whiteColor,
                  backgroundColor: CustomColor.primaryLightColor,
                  strokeWidth: 2.5,
                  onRefresh: () async {
                    controller.transactionInfoProcess();
                    return Future<void>.delayed(const Duration(seconds: 3));
                  },
                  child: ListView(
                    children: [
                      SizedBox(
                        height: MediaQuery.of(context).size.height * 0.6,
                        child: Center(
                          child: Text(
                            DynamicLanguage.key(
                                Strings.noTransactionsAvailable),
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: Dimensions.headingTextSize4,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  color: CustomColor.whiteColor,
                  backgroundColor: CustomColor.primaryLightColor,
                  strokeWidth: 2.5,
                  onRefresh: () async {
                    controller.transactionInfoProcess();
                    return Future<void>.delayed(const Duration(seconds: 3));
                  },
                  child: ListView.builder(
                    itemCount: controller.transactions.length,
                    padding: EdgeInsets.zero,
                    itemBuilder: (context, index) {
                      var data = controller.transactions[index];
                      return Padding(
                        padding: EdgeInsets.only(
                            left: Dimensions.paddingSize,
                            right: Dimensions.paddingSize),
                        child: CustomWalletCard(
                          statusColor: _getStatusColor(data.status),
                          statusText: _getStatusText(data.status),
                          status: data.status,
                          isSelected: true,
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
        ),
      ),
    );
  }

  Container _orderLogWidget(context) {
    return Container(
      color: CustomColor.blackColor.withValues(alpha: 0.05),
      child: Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize * 1.5),
        child: Container(
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
          child: controller.orders.isEmpty
              ? RefreshIndicator(
                  color: CustomColor.whiteColor,
                  backgroundColor: CustomColor.primaryLightColor,
                  strokeWidth: 2.5,
                  onRefresh: () async {
                    controller.orderInfoProcess();
                    return Future<void>.delayed(const Duration(seconds: 3));
                  },
                  child: ListView(
                    children: [
                      SizedBox(
                        height: MediaQuery.of(context).size.height * 0.6,
                        child: Center(
                          child: Text(
                            DynamicLanguage.key(Strings.noOrdersAvailable),
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: Dimensions.headingTextSize4,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  color: CustomColor.whiteColor,
                  backgroundColor: CustomColor.primaryLightColor,
                  strokeWidth: 2.5,
                  onRefresh: () async {
                    controller.orderInfoProcess();
                    return Future<void>.delayed(const Duration(seconds: 3));
                  },
                  child: ListView.builder(
                    itemCount: controller.orders.length,
                    padding: EdgeInsets.zero,
                    itemBuilder: (context, index) {
                      var data = controller.orders[index];
                      return Padding(
                        padding: EdgeInsets.only(
                            left: Dimensions.paddingSize,
                            right: Dimensions.paddingSize),
                        child: CustomWalletCard(
                          statusWidget: Container(
                            color: CustomColor.whiteColor,
                          ),
                          isSelected: true,
                          fontWeightTitle: FontWeight.w700,
                          walletTitle: data.item,
                          amount:
                              "${data.totalAmount.toStringAsFixed(0)} ${LocalStorage.baseCurrencyCode}",
                          fontSizeTitle: Dimensions.headingTextSize4,
                          fontWeightSubTitleMain: FontWeight.w400,
                          fontSizeSubtitle: Dimensions.headingTextSize4,
                          customWidget: DateInfoWidget(
                            dateText: dateOnly.format(data.date),
                            monthText: monthOnly.format(data.date),
                          ),
                        ),
                      );
                    },
                  ),
                ),
        ),
      ),
    );
  }

  Color _getStatusColor(int status) {
    switch (status) {
      case 1:
        return CustomColor.greenColor.withValues(alpha: 0.9);
      case 2:
        return CustomColor.orangeColor.withValues(alpha: 0.9);
      case 3:
        return CustomColor.pinkColor.withValues(alpha: 0.9);
      case 4:
        return CustomColor.redColor.withValues(alpha: 0.9);
      default:
        return CustomColor.orangeColor;
    }
  }

  String _getStatusText(int status) {
    switch (status) {
      case 1:
        return Strings.success;
      case 2:
        return Strings.pending;
      case 3:
        return Strings.hold;
      case 4:
        return Strings.rejected;
      default:
        return Strings.waiting;
    }
  }
}
