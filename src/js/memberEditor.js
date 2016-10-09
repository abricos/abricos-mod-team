var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var MemberEditorWidgetExt = function(){
    };
    MemberEditorWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,

        team: NS.ATTRIBUTE.team,
        memberid: NS.ATTRIBUTE.memberid,
        member: NS.ATTRIBUTE.member,
        policy: NS.ATTRIBUTE.policy,

        isEdit: {
            getter: function(){
                return this.get('memberid') > 0;
            }
        }
    };
    MemberEditorWidgetExt.prototype = {
        buildTData: function(){
            return {
                teamid: this.get('team').get('id')
            };
        },
        onInitAppWidget: function(err, appInstance){
            var teamApp = this.get('teamApp'),
                teamid = this.get('team').get('id'),
                policy= this.get('policy'),
                memberid = this.get('memberid'),
                member;

            this.triggerHide('userSearch');

            this.set('waiting', true);

            if (memberid > 0){
                teamApp.member(teamid, memberid, policy, function(err, result){
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
                teamid = this.get('team').get('id'),
                memberid = member.get('id');

            if (memberid > 0){
                var user = member.get('user');
                tp.setHTML({
                    userNameRO: user.get('username'),
                    firstNameRO: user.get('firstname'),
                    lastNameRO: user.get('lastname'),
                });
                this.triggerShow('userSearch', 'EDIT_ALLOWED, EXISTS');
            } else {
                if (tp.one('userInviteFormWidget')){
                    tp.show('searchPanel');
                    var widget = this.addWidget('userInviteForm', new Brick.mod.invite.UserInviteFormWidget({
                        srcNode: tp.one('userInviteFormWidget'),
                        owner: {
                            module: 'team',
                            type: this.get('policy'),
                            ownerid: teamid
                        },
                    }));
                    widget.on('request', this._onUserInviteRequest, this);
                    widget.on('response', this._onUserInviteResponse, this);
                }
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
                    userNameRO: user.get('username'),
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
                memberid = this.get('memberid'),
                userInviteForm = this.getWidget('userInviteForm'),
                data = {
                    memberid: memberid,
                    teamid: this.get('team').get('id'),
                    policy: this.get('policy'),
                    firstName: tp.getValue('firstName'),
                    lastName: tp.getValue('lastName'),
                };

            if (memberid === 0 && userInviteForm){
                data.invite = userInviteForm.toJSON();
            }
            return this.onFillToJSON(data);
        },
        save: function(){
            var data = this.toJSON(),
                callback = this.get('callback');

            this.set('waiting', true);
            this.get('teamApp').memberSave(data, function(err, result){
                this.set('waiting', false);

                if (!err){
                    this.onSave(result.memberSave);
                }
            }, this);
        },
        onSave: function(r){
        }
    };
    NS.MemberEditorWidgetExt = MemberEditorWidgetExt;
};
