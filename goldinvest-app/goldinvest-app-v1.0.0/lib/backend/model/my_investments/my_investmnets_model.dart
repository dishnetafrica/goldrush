import 'dart:convert';

MyInvestmentsModel myInvestmentsModelFromJson(String str) =>
    MyInvestmentsModel.fromJson(json.decode(str));

String myInvestmentsModelToJson(MyInvestmentsModel data) =>
    json.encode(data.toJson());

class MyInvestmentsModel {
  Message message;
  Data data;
  String type;

  MyInvestmentsModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory MyInvestmentsModel.fromJson(Map<String, dynamic> json) =>
      MyInvestmentsModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
        type: json["type"],
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
        "type": type,
      };
}

class Data {
  Instructions instructions;
  List<Invest> invest;

  Data({
    required this.instructions,
    required this.invest,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        instructions: Instructions.fromJson(json["instructions"]),
        invest:
            List<Invest>.from(json["invest"].map((x) => Invest.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "instructions": instructions.toJson(),
        "invest": List<dynamic>.from(invest.map((x) => x.toJson())),
      };
}

class Instructions {
  String status;

  Instructions({
    required this.status,
  });

  factory Instructions.fromJson(Map<String, dynamic> json) => Instructions(
        status: json["status"],
      );

  Map<String, dynamic> toJson() => {
        "status": status,
      };
}

class Invest {
  int userId;
  int investPlanId;
  int investAmount;
  String availableBalance;
  DateTime expAt;
  int status;
  InvestPlan investPlan;

  Invest({
    required this.userId,
    required this.investPlanId,
    required this.investAmount,
    required this.availableBalance,
    required this.expAt,
    required this.status,
    required this.investPlan,
  });

  factory Invest.fromJson(Map<String, dynamic> json) => Invest(
        userId: json["user_id"],
        investPlanId: json["invest_plan_id"],
        investAmount: json["invest_amount"],
        availableBalance: json["available_balance"],
        expAt: DateTime.parse(json["exp_at"]),
        status: json["status"],
        investPlan: InvestPlan.fromJson(json["invest_plan"]),
      );

  Map<String, dynamic> toJson() => {
        "user_id": userId,
        "invest_plan_id": investPlanId,
        "invest_amount": investAmount,
        "available_balance": availableBalance,
        "exp_at": expAt.toIso8601String(),
        "status": status,
        "invest_plan": investPlan.toJson(),
      };
}

class InvestPlan {
  int id;
  String name;
  String title;
  String slug;
  int planDuration;
  int profit;
  dynamic profitPercentage;
  String profitReturnType;

  InvestPlan({
    required this.id,
    required this.name,
    required this.title,
    required this.slug,
    required this.planDuration,
    required this.profit,
    required this.profitPercentage,
    required this.profitReturnType,
  });

  factory InvestPlan.fromJson(Map<String, dynamic> json) => InvestPlan(
        id: json["id"],
        name: json["name"],
        title: json["title"],
        slug: json["slug"],
        planDuration: json["plan_duration"],
        profit: json["profit"],
        profitPercentage: json["profit_percentage"],
        profitReturnType: json["profit_return_type"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "title": title,
        "slug": slug,
        "plan_duration": planDuration,
        "profit": profit,
        "profit_percentage": profitPercentage,
        "profit_return_type": profitReturnType,
      };
}

class Message {
  List<String> success;

  Message({
    required this.success,
  });

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}
