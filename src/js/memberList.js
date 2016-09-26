var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var MemberListRowWidgetExt = function(){
    };
    MemberListRowWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        member: {value: null}
    };
    MemberListRowWidgetExt.prototype = {
        buildTData: function(){
            return this.get('member').toReplace();
        },
        onInitAppWidget: function(err, appInstance, options){
            // this.appURLUpdate();

            this.onRenderMember();
        },
        onRenderMember: function(){
        },
    };
    NS.MemberListRowWidgetExt = MemberListRowWidgetExt;

    var MemberListWidgetExt = function(){
    };
    MemberListWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        memberList: {value: null},
        memberListFilter: NS.ATTRIBUTE.memberListFilter,
    };
    MemberListWidgetExt.prototype = {
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
            this.closeAction();
            this._cleanMemberList();
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
                ownerModule = appInstance.get('moduleName'),
                tp = this.template,
                wsList = this._cleanMemberList(),
                MemberListRowWidget = Brick.mod[ownerModule].MemberListRowWidget || NS.MemberListRowWidget;

            memberList.each(function(member){
                var w = new MemberListRowWidget({
                    boundingBox: tp.append('list', tp.replace('itemWrap')),
                    member: member,
                    CLICKS: {
                        edit: {
                            context: this,
                            event: function(){
                                this.showEditor(member.get('id'));
                            }
                        }
                    }
                });
                wsList[wsList.length] = w;
            }, this);

            return this.onLoadMemberList(memberList);
        },
        onLoadMemberList: function(memberList){
        },
        _cleanMemberList: function(){
            var list = this._wsList;
            for (var i = 0; i < list.length; i++){
                list[i].destroy();
            }
            return this._wsList = [];
        },
        closeAction: function(){
            if (this.actionWidget){
                this.actionWidget.destroy();
                this.actionWidget = null;
            }
            var tp = this.template;
            tp.toggleView(false, 'action', 'list,buttons');
        },
        _actionCallback: function(memberList){
            if (memberList){
                this.set('memberList', memberList);
            }
            this._onLoadMemberList(memberList);
            this.closeAction();
        },
        _showAction: function(actionType, member){
            var tp = this.template,
                ActionWidget = actionType === 'editor'
                    ? NS.MemberEditorWidget
                    : NS.MemberRemoveWidget;

            tp.toggleView(true, 'action', 'list,buttons');

            this.actionWidget = new ActionWidget({
                srcNode: tp.append('action', '<div></div>'),
                member: member,
                callbackContext: this,
                callback: this._actionCallback
            });
        },
        showEditor: function(memberid){
            memberid = memberid | 0;

            var appInstance = this.get('appInstance'),
                ownerModule = appInstance.get('moduleName'),
                tp = this.template,
                filter = this.get('memberListFilter'),
                teamid = 0,
                MemberEditorWidget = Brick.mod[ownerModule].MemberEditorWidget;

            if (!MemberEditorWidget){
                return;
            }

            if (memberid === 0 && filter.method === 'team'){
                teamid = filter.teamid | 0;
            } else if (memberid > 0){
                var member = this.get('memberList').getById(memberid);
                if (!member){
                    return;
                }
                teamid = member.get('teamid');
            }

            if (teamid === 0){
                return;
            }

            tp.toggleView(true, 'action', 'list,buttons');

            this.actionWidget = new MemberEditorWidget({
                srcNode: tp.append('action', '<div></div>'),
                teamid: teamid,
                memberid: memberid,
                callbackContext: this,
                callback: this._actionCallback
            });
        },
        showRemove: function(itemid){
            itemid = itemid | 0;

            var member = this.get('memberList').getById(itemid);

            this._showAction('remove', member);
        },
    };
    NS.MemberListWidgetExt = MemberListWidgetExt;
};
