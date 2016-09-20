var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var MemberEditorWidgetExt = function(){
    };
    MemberEditorWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,

        teamid: NS.ATTRIBUTE.teamid,
        memberid: NS.ATTRIBUTE.teamid,
        member: NS.ATTRIBUTE.member,

        callback: {value: null},
        callbackContext: {value: null},

        isEdit: {
            getter: function(){
                return this.get('memberid') > 0;
            }
        }
    };
    MemberEditorWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            var teamApp = this.get('teamApp'),
                teamid = this.get('teamid'),
                memberid = this.get('memberid'),
                member;

            this.triggerHide('userSearch');

            this.set('waiting', true);
            if (memberid > 0){
                teamApp.member(teamid, memberid, function(err, result){
                    member = err ? null : result.member;
                    this._onLoadMember(result.member);
                }, this);
            } else {
                member = new NS.Member({
                    appInstance: teamApp,
                    teamid: this.get('teamid'),
                    module: appInstance.get('moduleName')
                });

                Brick.use('invite', 'form', function(){
                    NS.initApps('invite', function(){
                        this._onLoadMember(member);
                    }, this);
                }, this);
            }
        },
        _onLoadMember: function(member){
            this.set('waiting', false);
            this.set('member', member);

            if (!member){
                // TODO: show member not found error
                return;
            }

            var tp = this.template,
                teamid = this.get('teamid');

            if (tp.one('userInviteFormWidget')){
                var widget = this.addWidget('userInviteForm', new Brick.mod.invite.UserInviteFormWidget({
                    srcNode: tp.one('userInviteFormWidget'),
                    owner: {
                        module: 'team',
                        type: this.get('appInstance').get('moduleName'),
                        ownerid: this.get('teamid')
                    },
                }));
                widget.on('request', this._onUserInviteRequest, this);
                widget.on('response', this._onUserInviteResponse, this);
            }

            this.onLoadMember(member);
        },
        onLoadMember: function(member){
        },
        _onUserInviteRequest: function(e){
            this.set('waiting', true);
            this.triggerHide('userSearch');

            this.onUserInviteRequest(e);
        },
        onUserInviteRequest: function(e){
        },
        _onUserInviteResponse: function(e){
            this.set('waiting', false);

            if (e.error){
                return;
            }

            var tp = this.template,
                rUS = e.result,
                codes = rUS.getCodesIsSet(),
                user = rUS.get('user');

            if (user){
                tp.setHTML({
                    firstNameRO: user.get('firstname'),
                    lastNameRO: user.get('lastname'),
                });
                tp.setValue({
                    firstName: user.get('firstname'),
                    lastName: user.get('lastname'),
                });
            }

            this.triggerShow('userSearch', codes);

            this.onUserInviteResponse(e);
        },
        onUserInviteResponse: function(e){
        },
        onFillToJSON: function(data){
            return data;
        },
        toJSON: function(){
            var tp = this.template,
                member = this.get('member'),
                teamid = this.get('teamid'),
                callback = this.get('callback'),
                userInviteForm = this.getWidget('inviteByEmail'),
                data = {
                    id: member.get('id'),
                    teamid: teamid,
                    firstName: tp.getValue('firstName'),
                    lastName: tp.getValue('lastName'),
                };

            if (userInviteForm){
                data = Y.merge(userInviteForm.toJSON(), data);
            }
            return this.onFillToJSON(data);
        },
        save: function(){

            var tp = this.template,
                teamid = this.get('teamid'),
                data = this.toJSON(),
                callback = this.get('callback');


            this.set('waiting', true);
            this.get('teamApp').memberSave(teamid, data, function(err, result){
                this.set('waiting', false);

                var memberList = err ? null : result.memberList;
                if (Y.Lang.isFunction(callback)){
                    callback.call(this.get('callbackContext'), memberList);
                }
            }, this);
        },
        cancel: function(){
            var callback = this.get('callback');
            if (Y.Lang.isFunction(callback)){
                callback.call(this.get('callbackContext'), null);
            }
        },
        onClick1: function(e){
            switch (e.dataClick) {
                case 'save':
                    this.save();
                    return true;
            }
        }
    };
    NS.MemberEditorWidgetExt = MemberEditorWidgetExt;
};