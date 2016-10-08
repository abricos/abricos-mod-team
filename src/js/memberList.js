var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var MemberListItemWidgetExt = function(){
    };
    MemberListItemWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        member: {value: null}
    };
    MemberListItemWidgetExt.prototype = {
        buildTData: function(){
            return {
                memberid: this.get('member').get('id')
            };
        },
        onInitAppWidget: function(err, appInstance, options){
            // this.appURLUpdate();

            this.onRenderMember();
        },
        onRenderMember: function(){
        },
    };
    NS.MemberListItemWidgetExt = MemberListItemWidgetExt;

    var MemberListWidgetExt = function(){
    };
    MemberListWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        team: NS.ATTRIBUTE.team,
        policy: NS.ATTRIBUTE.policy,
        memberList: {value: null},
        memberListFilter: NS.ATTRIBUTE.memberListFilter,
        itemWidget: {value: null}
    };
    MemberListWidgetExt.prototype = {
        buildTData: function(){
            return {
                teamid: this.get('team').get('id')
            };
        },
        onInitAppWidget: function(err, appInstance){
            this._wsList = [];

            var memberList = this.get('memberList');
            if (!memberList){
                this.reloadMemberList();
            } else {
                this._onLoadMemberList(memberList);
            }
        },
        destructor: function(){
            this._cleanMemberList();
        },
        _cleanMemberList: function(){
            var list = this._wsList;
            for (var i = 0; i < list.length; i++){
                list[i].destroy();
            }
            return this._wsList = [];
        },
        reloadMemberList: function(){
            var teamApp = this.get('teamApp'),
                filter = this.get('memberListFilter');

            this.set('waiting', true);
            teamApp.memberList(filter, function(err, result){
                var memberList = err ? null : result.memberList.get('items');
                this._onLoadMemberList(memberList);
            }, this);
        },
        _onLoadMemberList: function(memberList){
            this.set('waiting', false);
            this.set('memberList', memberList);

            if (!memberList){
                return this.onLoadMemberList(null);
            }

            var appInstance = this.get('appInstance'),
                tp = this.template,
                wsList = this._cleanMemberList(),
                ItemWidget = this.get('itemWidget');

            if (!ItemWidget){
                return;
            }
            memberList.each(function(member){
                var w = new ItemWidget({
                    boundingBox: tp.append('list', tp.replace('itemWrap')),
                    member: member,
                });
                wsList[wsList.length] = w;
            }, this);

            return this.onLoadMemberList(memberList);
        },
        onLoadMemberList: function(memberList){
        },
    };
    NS.MemberListWidgetExt = MemberListWidgetExt;
};
